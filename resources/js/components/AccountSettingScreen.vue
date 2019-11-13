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
      <h2 class="c-title">設定</h2>

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
            <span class="u-fs-4">
              <i class="fas fa-info-circle"></i>「ターゲットアカウント」のフォロワーのうち、「フォローキーワード」をプロフィールに含むアカウントを自動でフォローします。
            </span>
            <div class="c-form-group">
              <label for="email" class="c-form-group__label">
                ・ターゲットアカウント
                <span class="u-fs-4">@マークを除いたユーザー名を指定してください。</span>
              </label>
              <string-list-manager
                v-model="targetAccountArray"
                :placeholder="'例）tanakaTaro'"
                :maxLength="20"
                :maxCount="20"
              ></string-list-manager>
            </div>
            <div class="c-form-group">
              <label for="keyword-follow" class="c-form-group__label">
                ・フォローキーワード
                <span>
                  <a href="/help/keyword" class="u-fs-4" target="_blank">キーワードの指定について</a>
                </span>
              </label>
              <string-list-manager
                v-model="followKeywordArray"
                :placeholder="'例）HTML (※50文字まで)'"
                :maxLength="50"
                :maxCount="20"
              ></string-list-manager>
            </div>
          </fieldset>
          <fieldset class="c-form-fieldset">
            <legend>自動アンフォロー関連</legend>
            <span class="u-fs-4">
              <i class="fas fa-info-circle"></i>ご指定の設定で自動でアンフォローを行います。
            </span>
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

            <span class="u-fs-4">
              <i class="fas fa-info-circle"></i>「いいねキーワード」を含むツイートを自動でいいねします。
            </span>
            <div class="c-form-group">
              <label for="email" class="c-form-group__label">
                ・いいねキーワード
                <span>
                  <a href="/help/keyword" class="u-fs-4" target="_blank">キーワードの指定について</a>
                </span>
              </label>
              <string-list-manager
                v-model="favoriteKeywordArray"
                :placeholder="'例）東京(※50文字まで)'"
                :maxLength="50"
                :maxCount="20"
              ></string-list-manager>
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
      followKeywordArray: [],
      favoriteKeywordArray: [],
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
          res.data[0].target_accounts !== ""
            ? (this.targetAccountArray = res.data[0].target_accounts.split(","))
            : (this.targetAccountArray = []);
          res.data[0].keyword_follow !== ""
            ? (this.followKeywordArray = res.data[0].keyword_follow.split(","))
            : (this.followKeywordArray = []);
          res.data[0].keyword_favorite !== ""
            ? (this.favoriteKeywordArray = res.data[0].keyword_favorite.split(
                ","
              ))
            : (this.favoriteKeywordArray = []);
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
          keyword_follow: this.followKeywordArray.join(","),
          keyword_favorite: this.favoriteKeywordArray.join(","),
          target_accounts: this.targetAccountArray.join(",")
        })
        .then(res => {
          this.flash("設定を保存しました。「アカウント一覧・稼働状況」から稼働状況を変更できます。", "success", {
            timeout: 5000,
            important: true
          });
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
            res.data[0].keyword_follow !== ""
              ? (this.followKeywordArray = res.data[0].keyword_follow.split(
                  ","
                ))
              : (this.followKeywordArray = []);
            res.data[0].keyword_favorite !== ""
              ? (this.favoriteKeywordArray = res.data[0].keyword_favorite.split(
                  ","
                ))
              : (this.favoriteKeywordArray = []);
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
