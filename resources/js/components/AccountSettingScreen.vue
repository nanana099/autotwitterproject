<template>
  <!-- 各アカウント設定画面 -->
  <div>
    <div class="p-select-account">
      <label for class="p-select-account__label">
        操作中のアカウント：
        <account-select-box :accounts="accounts" @changeAccount="onChangeAccount"></account-select-box>
      </label>
    </div>
    <section class="p-section">
      <h2 class="c-title">自動機能設定</h2>

      <div v-show="isLoading">
        <span class="p-message-1">Loading...</span>
      </div>
      <div v-show="!isLoading">
        <div v-show="!existsAccount">
          <span class="p-message-1">
            <i class="fas fa-info-circle u-mr-2"></i>Twitterアカウントが登録されていません。
            <a href="/mypage/monitor">アカウント一覧</a>から登録してください。
          </span>
        </div>
        <div v-show="existsAccount">
          <fieldset class="c-form-fieldset">
            <legend>自動フォロー関連</legend>
            <div class="c-justify-content-start u-fs-4">
              <i class="fas fa-info-circle u-mr-1"></i>
              <span>「ターゲットアカウント」のフォロワーのうち、「フォローキーワード」をプロフィールに含むアカウントを自動でフォローします。</span>
            </div>
            <div class="c-form-group">
              <label for="email" class="c-form-group__label u-mb-2">・ターゲットアカウント</label>
              <span class="u-fs-4">@マークを除いたユーザー名を指定してください。</span>
              <string-list-manager
                v-model="targetAccountArray"
                :placeholder="'例）tanakaTaro'"
                :maxLength="20"
                :maxCount="20"
              ></string-list-manager>
            </div>
            <div class="c-form-group">
              <label for="keyword-follow" class="c-form-group__label u-mb-2">・フォローキーワード</label>
              <div class="c-justify-content-between-md">
                <div class="c-column u-mr-2 u-mb-3">
                  <span class="u-text-center u-d-inline-block u-mb-2">AND（必ず含む）</span>
                  <string-list-manager
                    v-model="followKeywordArrayAND"
                    :placeholder="'例）HTML'"
                    :maxLength="50"
                    :maxCount="20"
                  ></string-list-manager>
                </div>

                <div class="c-column u-mr-2 u-mb-3">
                  <span class="u-text-center u-d-inline-block u-mb-2">OR（いずれか含む）</span>
                  <string-list-manager
                    v-model="followKeywordArrayOR"
                    :placeholder="'例）プログラミング'"
                    :maxLength="50"
                    :maxCount="20"
                  ></string-list-manager>
                </div>

                <div class="c-column u-mr-2 u-mb-3">
                  <span class="u-text-center u-d-inline-block u-mb-2">NOT（含まない）</span>
                  <string-list-manager
                    v-model="followKeywordArrayNOT"
                    :placeholder="'例）公式'"
                    :maxLength="50"
                    :maxCount="20"
                  ></string-list-manager>
                </div>
              </div>
            </div>
          </fieldset>
          <fieldset class="c-form-fieldset">
            <legend>自動アンフォロー関連</legend>
            <div class="c-justify-content-start u-fs-4">
              <i class="fas fa-info-circle u-mr-1"></i>
              <span>ご指定の設定で自動でアンフォローを行います。</span>
            </div>
            <div class="c-form-group">
              <div>
                <label for class>
                  ・フォローしてから
                  <input
                    id
                    type="number"
                    class="form-control"
                    v-model.number="setting.days_unfollow_user"
                    min="1"
                    max="999"
                  />
                  日間、フォローが返って来ない場合にアンフォローする
                </label>
              </div>
              <div class="u-fs-4">※７日以上をご指定ください</div>

              <span class="c-invalid-feedback" role="alert">{{msgDaysUnfollowUser}}</span>
            </div>

            <div class="c-form-group">
              <label for="unfollow-inactive" class>
                ・
                <input
                  type="checkbox"
                  name="unfollow-inactive"
                  id="unfollow-inactive"
                  v-model="setting.bool_unfollow_inactive"
                />
                15日間投稿の無いユーザーをアンフォローする
              </label>
            </div>
          </fieldset>
          <fieldset class="c-form-fieldset">
            <legend>自動いいね関連</legend>

            <div class="c-justify-content-start u-fs-4">
              <i class="fas fa-info-circle u-mr-1"></i>
              <span>「いいねキーワード」を含むツイートを自動でいいねします。</span>
            </div>

            <div class="c-form-group">
              <label for="keyword-follow" class="c-form-group__label u-mb-2">・いいねキーワード</label>
              <div class="c-justify-content-between-md">
                <div class="c-column u-mr-2 u-mb-3">
                  <span class="u-text-center u-d-inline-block u-mb-2">AND（必ず含む）</span>

                  <string-list-manager
                    v-model="favoriteKeywordArrayAND"
                    :placeholder="'例）東京'"
                    :maxLength="50"
                    :maxCount="20"
                  ></string-list-manager>
                </div>
                <div class="c-column u-mr-2 u-mb-3">
                  <span class="u-text-center u-d-inline-block u-mb-2">OR（いずれか含む）</span>
                  <string-list-manager
                    v-model="favoriteKeywordArrayOR"
                    :placeholder="'例）大阪'"
                    :maxLength="50"
                    :maxCount="20"
                  ></string-list-manager>
                </div>
                <div class="c-column u-mr-2 u-mb-3">
                  <span class="u-text-center u-d-inline-block u-mb-2">NOT（含まない）</span>
                  <string-list-manager
                    v-model="favoriteKeywordArrayNOT"
                    :placeholder="'例）東京事変'"
                    :maxLength="50"
                    :maxCount="20"
                  ></string-list-manager>
                </div>
              </div>
            </div>
          </fieldset>

          <div class="c-justify-content-end">
            <button class="c-btn c-btn--primary c-btn--large u-mr-2" @click="saveSetting">保存</button>
          </div>
        </div>
      </div>
    </section>
    <flash-message class="p-flash_message--fixed"></flash-message>
  </div>
</template>



<script>
import AccountSelectBox from "./AccountSelectBox";
import StringListManager from "./StringListManager";
export default {
  components: {
    "account-select-box": AccountSelectBox,
    "string-list-manager": StringListManager
  },
  data: function() {
    return {
      accounts: [],
      setting: {},
      targetAccountArray: [],
      followKeywordArrayAND: [],
      followKeywordArrayOR: [],
      followKeywordArrayNOT: [],
      favoriteKeywordArrayAND: [],
      favoriteKeywordArrayOR: [],
      favoriteKeywordArrayNOT: [],
      msgDaysUnfollowUser: "",
      isLoading: true
    };
  },
  methods: {
    onChangeAccount: function(id) {
      // 操作中のアカウント変更時に、アカウントの設定情報をDBから取得する
      this.isLoading = true;
      axios
        .get("/account/setting", {
          params: {
            account_id: id
          }
        })
        .then(res => {
          this.setting = res.data[0];
          // ターゲットアカウント
          res.data[0].target_accounts !== ""
            ? (this.targetAccountArray = res.data[0].target_accounts.split(","))
            : (this.targetAccountArray = []);
          // フォローキーワード
          res.data[0].keyword_follow_and !== ""
            ? (this.followKeywordArrayAND = res.data[0].keyword_follow_and.split(
                ","
              ))
            : (this.followKeywordArrayAND = []);
          res.data[0].keyword_follow_or !== ""
            ? (this.followKeywordArrayOR = res.data[0].keyword_follow_or.split(
                ","
              ))
            : (this.followKeywordArrayOR = []);
          res.data[0].keyword_follow_not !== ""
            ? (this.followKeywordArrayNOT = res.data[0].keyword_follow_not.split(
                ","
              ))
            : (this.followKeywordArrayNOT = []);
          // いいねキーワード
          res.data[0].keyword_favorite_and !== ""
            ? (this.favoriteKeywordArrayAND = res.data[0].keyword_favorite_and.split(
                ","
              ))
            : (this.favoriteKeywordArrayAND = []);
          res.data[0].keyword_favorite_or !== ""
            ? (this.favoriteKeywordArrayOR = res.data[0].keyword_favorite_or.split(
                ","
              ))
            : (this.favoriteKeywordArrayOR = []);
          res.data[0].keyword_favorite_not !== ""
            ? (this.favoriteKeywordArrayNOT = res.data[0].keyword_favorite_not.split(
                ","
              ))
            : (this.favoriteKeywordArrayNOT = []);
          this.isLoading = false;
        })
        .catch(error => {
          this.isError = true;
        });
    },
    saveSetting: function() {
      // バリデーション
      this.setting.days_unfollow_user === 0 ||
      this.setting.days_unfollow_user < 7 ||
      this.setting.days_unfollow_user > 999
        ? (this.msgDaysUnfollowUser = "7~999を入力してください")
        : (this.msgDaysUnfollowUser = "");

      if (this.msgDaysUnfollowUser !== "") {
        // エラーがある場合は保存処理しない
        this.flash(
          "エラーが発生しました。入力内容をご確認ください。",
          "error",
          {
            timeout: 0,
            important: false
          }
        );
        return;
      }

      // 設定保存
      axios
        .post("/account/setting", {
          account_setting_id: this.setting.id,
          days_inactive_user: this.setting.days_inactive_user,
          days_unfollow_user: this.setting.days_unfollow_user,
          num_max_unfollow_per_day: this.setting.num_max_unfollow_per_day,
          num_user_start_unfollow: this.setting.num_user_start_unfollow,
          bool_unfollow_inactive: this.setting.bool_unfollow_inactive,
          account_id: this.setting.account_id,
          keyword_follow_and: this.followKeywordArrayAND.join(","),
          keyword_follow_or: this.followKeywordArrayOR.join(","),
          keyword_follow_not: this.followKeywordArrayNOT.join(","),
          keyword_favorite_and: this.favoriteKeywordArrayAND.join(","),
          keyword_favorite_or: this.favoriteKeywordArrayOR.join(","),
          keyword_favorite_not: this.favoriteKeywordArrayNOT.join(","),
          target_accounts: this.targetAccountArray.join(",")
        })
        .then(res => {
          this.flash(
            "設定を保存しました。「アカウント一覧・稼働状況」から稼働状況を変更できます。",
            "success",
            {
              timeout: 5000,
              important: true
            }
          );
        })
        .catch(error => {
          this.flash(
            "設定を保存できませんでした。しばらく経ってから再度お試しください",
            "error",
            {
              timeout: 0,
              important: false
            }
          );
        });
    }
  },
  created: function() {
    axios
      .get("/account/get", {}) // アカウント一覧取得
      .then(res => {
        this.accounts = res.data;
        let targetId = localStorage.selectedId;
        let isSelectedAccount = false;
        this.accounts.forEach(x => {
          if (x.id === Number(targetId)) {
            isSelectedAccount = true;
          }
        });
        if (!isSelectedAccount) {
          // 操作中のアカウントが未選択の場合
          this.flash("アカウントを選択してください", "info", {
            timeout: 0,
            important: false
          });
          this.isLoading = false;
          return;
        }
        axios
          .get("/account/setting", {
            params: {
              account_id: targetId
            }
          }) // 選択中のアカウントの設定情報を取得
          .then(res => {
            this.setting = res.data[0];
            res.data[0].target_accounts !== ""
              ? (this.targetAccountArray = res.data[0].target_accounts.split(
                  ","
                ))
              : (this.targetAccountArray = []);
            // フォローキーワード
            res.data[0].keyword_follow_and !== ""
              ? (this.followKeywordArrayAND = res.data[0].keyword_follow_and.split(
                  ","
                ))
              : (this.followKeywordArrayAND = []);
            res.data[0].keyword_follow_or !== ""
              ? (this.followKeywordArrayOR = res.data[0].keyword_follow_or.split(
                  ","
                ))
              : (this.followKeywordArrayOR = []);
            res.data[0].keyword_follow_not !== ""
              ? (this.followKeywordArrayNOT = res.data[0].keyword_follow_not.split(
                  ","
                ))
              : (this.followKeywordArrayNOT = []);
            // いいねキーワード
            res.data[0].keyword_favorite_and !== ""
              ? (this.favoriteKeywordArrayAND = res.data[0].keyword_favorite_and.split(
                  ","
                ))
              : (this.favoriteKeywordArrayAND = []);
            res.data[0].keyword_favorite_or !== ""
              ? (this.favoriteKeywordArrayOR = res.data[0].keyword_favorite_or.split(
                  ","
                ))
              : (this.favoriteKeywordArrayOR = []);
            res.data[0].keyword_favorite_not !== ""
              ? (this.favoriteKeywordArrayNOT = res.data[0].keyword_favorite_not.split(
                  ","
                ))
              : (this.favoriteKeywordArrayNOT = []);
            this.isLoading = false;
          })
          .catch(error => {
            this.flash(
              "情報の取得に失敗しました。しばらく経ってから再度お試しください",
              "error",
              {
                timeout: 0,
                important: false
              }
            );
          });
      })
      .catch(error => {
        this.flash(
          "情報の取得に失敗しました。しばらく経ってから再度お試しください",
          "error",
          {
            timeout: 0,
            important: false
          }
        );
      });
  },
  computed: {
    existsAccount: function() {
      return this.accounts.length > 0;
    }
  }
};
</script>
