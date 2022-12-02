<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Account;
use App\AccountSetting;
use App\OperationStatus;
use App\Services\TwitterAuth;
use App\Services\TwitterAccount;
use \Exception;
use App\Services\KeywordOperatorAnalyzer;

// アカウント情報に関連する操作を行う
class AccountController extends Controller
{
    // システムにTwitterアカウントを追加する
    public function add()
    {
        // 登録可能なアカウントを制限する
        $max_account = 10;
        
        if (Auth::user()->accounts()->count() >= $max_account) {
            // １ユーザーが登録できるアカウント数に上限を設ける
            return redirect()->route('mypage.monitor')->with('flash_message_error', '登録できるアカウントは'.$max_account.'個までです。');
        } else {
            try {
                $authUrl = TwitterAuth::getAuthorizeUrl();
                return redirect($authUrl);
            } catch (Exception $e) {
                logger()->error($e);
                return redirect()->route('mypage.monitor')->with('flash_message_error', '現在、アカウントが追加できません。しばらく立ってから再度お試しください。');
            }
        }
    }

    // ログイン中のユーザーが登録済みのアカウントの数を返す
    public function count()
    {
        return response()->json(Auth::user()->accounts()->count());
    }

    // Twitterの連携のときに、Twitterから呼ばれるコールバック関数
    public function callback()
    {
        $accessToken = TwitterAuth::getAccessToken();
        if (!$accessToken) {
            // アプリの認証をキャンセルした場合
            return redirect()->route('mypage.monitor')->with('flash_message_error', 'アカウントを追加できませんでした。');
        }

        $twitter_user_id = $accessToken['user_id'];
        $account = Account::where('twitter_user_id', $twitter_user_id)->get();

        if (count($account) > 0 && $account[0]['user_id'] !== Auth::id()) {
            // すでにTwitterアカウントが他のユーザーによって登録済みの場合は不可
            return redirect()->route('mypage.monitor')->with('flash_message_error', 'Twitterアカウントが他のユーザにより登録済みのため、登録できませんでした。');
        } else {
            try {
                $accessTokenStr = json_encode($accessToken);

                $twitterAccount = new TwitterAccount($accessTokenStr);
                $twitterAccountInfo = $twitterAccount->getMyAccountInfo();
                $screen_name = $twitterAccountInfo['screen_name'];
                $image_url = $twitterAccountInfo['profile_image_url_https'];
            
                $msg = '';
                if (count($account) > 0) {
                    $msg = 'すでに登録されたアカウントです。';
                } else {
                    $msg = 'Twitterアカウントの登録に成功しました。自動化するためには「設定」を行いましょう！';
                }

                // accounts：アカウント情報管理用。行がなければINSERT。行があればUPDATE（アクセストークン切れ等の場合更新が必要だから）
                // account_settings：アカウントの設定管理用。行がなければINSERT。行があれば何もしない
                // operation_statuses：アカウントの稼働状況管理よう。行がなければINSERT。行があれば何もしない
                $account = Account::updateOrCreate(['twitter_user_id' => $twitter_user_id], ['access_token' => $accessTokenStr,'user_id' => Auth::id(),'screen_name' => $screen_name, 'image_url' => $image_url]);
                AccountSetting::firstOrCreate(['account_id' => $account['id']], ['target_accounts' => '']);
                OperationStatus::firstOrCreate(['account_id' =>$account['id']]);
    
                
                return redirect()->route('mypage.monitor')->with('flash_message_success', $msg);
            } catch (Exception $e) {
                logger()->error($e);
                return redirect()->route('mypage.monitor')->with('flash_message_error', '現在、アカウントが追加できません。しばらく立ってから再度お試しください。');
            }
        }
    }

    // アカウント削除
    public function destroy(Request $request)
    {
        try {
            $account = Auth::user()->accounts()->find($request['id']);
            DB::transaction(function () use ($account) {
                // accountsテーブルに外部さん参照があるテーブルすべてを削除。
                $account->operationStatus->delete();
                $account->accountSetting->delete();
                $account->reservedTweets()->delete();
                $account->followedUsers()->delete();
                $account->unfollowedUsers()->delete();
                $account->delete();
            });
            return response()->json($account);
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }

    // ユーザーのアカウントすべての情報を返す
    public function get()
    {
        try {
            $accounts = Auth::user()->accounts()->get();
            return response()->json($accounts);
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }

    // 指定のアカウントの設定情報を返す
    public function getSetting(Request $request)
    {
        try {
            $account_id = $request['account_id'];
            $setting = Auth::user()->accounts()->find($account_id)->accountSetting()->get();
            $setting = $setting->toArray();

            $follow_str_and = '';
            $follow_str_or = '';
            $follow_str_not = '';
            $favorite_str_and = '';
            $favorite_str_or = '';
            $favorite_str_not = '';
            KeywordOperatorAnalyzer::operatorStrToCSV($setting[0]['keyword_follow'], $follow_str_and, $follow_str_or, $follow_str_not);
            KeywordOperatorAnalyzer::operatorStrToCSV($setting[0]['keyword_favorite'], $favorite_str_and, $favorite_str_or, $favorite_str_not);
            // DBに保存してある文字列を、AND/OR/NOTに分割
            $setting[0]['keyword_follow_and'] = $follow_str_and;
            $setting[0]['keyword_follow_or'] = $follow_str_or;
            $setting[0]['keyword_follow_not'] =$follow_str_not;
            $setting[0]['keyword_favorite_and'] = $favorite_str_and;
            $setting[0]['keyword_favorite_or'] = $favorite_str_or;
            $setting[0]['keyword_favorite_not'] = $favorite_str_not;
            return response()->json($setting);
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }

    // 指定のアカウント設定情報を更新する
    public function postSetting(Request $request)
    {
        try {
            // 空欄で送信されるとnullになる。DB上null非許容なので、空文字を入れておく。
            if (empty($request['target_accounts'])) {
                $request['target_accounts'] = '';
            }
            // フォローキーワード
            if (empty($request['keyword_follow_and'])) {
                $request['keyword_follow_and'] = '';
            }
            if (empty($request['keyword_follow_or'])) {
                $request['keyword_follow_or'] = '';
            }
            if (empty($request['keyword_follow_not'])) {
                $request['keyword_follow_not'] = '';
            }
            // いいねキーワード
            if (empty($request['keyword_favorite_and'])) {
                $request['keyword_favorite_and'] = '';
            }
            if (empty($request['keyword_favorite_or'])) {
                $request['keyword_favorite_or'] = '';
            }
            if (empty($request['keyword_favorite_not'])) {
                $request['keyword_favorite_not'] = '';
            }

            // TwitterAPIが解釈可能な演算子形式に変換
            $request['keyword_follow'] = KeywordOperatorAnalyzer::csvToOperatorStr($request['keyword_follow_and'], $request['keyword_follow_or'], $request['keyword_follow_not']);
            $request['keyword_favorite'] = KeywordOperatorAnalyzer::csvToOperatorStr($request['keyword_favorite_and'], $request['keyword_favorite_or'], $request['keyword_favorite_not']);
                
            $setting = Auth::user()->accountAccountSetting()->find($request['account_setting_id']);
            return response()->json($setting->fill($request->all())->save());
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }

    // 指定のアカウントの予約ツイートすべてを返す
    public function getTweet(Request $request)
    {
        try {
            $account_id = $request['account_id'] ;
            $tweets = Auth::user()->accounts()->find($account_id)->reservedTweets()->orderBy('submit_date', 'desc')->get();
            return response()->json($tweets);
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }

    // 指定のアカウントに予約ツイートを登録する
    public function postTweet(Request $request)
    {
        try {
            $account_id = $request['account_id'] ;
            $tweet_id = $request['reserved_tweet_id'];
            $result = Auth::user()->accounts()->find($account_id)->reservedTweets()->updateOrcreate(['id' => $tweet_id], $request->all());
            return response()->json($result);
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }

    // 指定の予約ツイートを削除する
    public function destroyTweet(Request $request)
    {
        try {
            $account_id = $request['account_id'] ;
            $tweet_id = $request['id'];
            $result = Auth::user()->accounts()->find($account_id)->reservedTweets()->find($tweet_id)->delete();
            return response()->json($result);
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }

    // ユーザーのアカウントすべての自動機能稼働状況を返す
    public function getStatus(Request $request)
    {
        try {
            $status = Auth::user()->accounts()->with('operationStatus')->get();
            return response()->json($status);
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }

    // 指定のアカウントの自動機能稼働状況を変更する
    public function postStatus(Request $request)
    {
        try {
            $type = $request['type'];
            $value = $request['value'];
            $operation_status_id = $request['operation_status_id'];
            $status = Auth::user()->accountOperationStatus()->find($operation_status_id);
            $data = array();
    
            switch ($type) {
                case 'follow':
                    $data['is_follow'] = $value;
                    break;
                case 'unfollow':
                    $data['is_unfollow'] = $value;
                    break;
                case 'favorite':
                    $data['is_favorite'] = $value;
                    break;
            }
            // ユーザーが手動で更新する場合は、凍結中フラグを倒す
            $data['is_flozen'] = false;
            
            return response()->json($status->fill($data)->save());
        } catch (Exception $e) {
            logger()->error($e);
            throw $e;
        }
    }
}
