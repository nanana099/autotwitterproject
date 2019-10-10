<template>
  <div>
    <div class="p-select-account">
      <label for class="p-select-account__label">
        操作中のアカウント：
        <account-select-box :accounts="accounts" @changeAccount="onChangeAccount"></account-select-box>
      </label>
    </div>
    <h2 class="c-title">設定</h2>
    <section class="p-section">
      <fieldset class="c-form-fieldset">
        <legend>自動フォロー関連</legend>
        <div class="c-form-group">
          <label for="keyword-follow" class="c-form-group__label">・フォローキーワード</label>
          <string-list-manager v-model="followKeywordArray" :placeholder="'例）HTML'"></string-list-manager>
        </div>
        <div class="c-form-group">
          <label for="email" class="c-form-group__label">・ターゲットアカウント</label>
          <string-list-manager v-model="targetAccountArray" :placeholder="'例）tanakaTaro'"></string-list-manager>
        </div>
      </fieldset>
      <fieldset class="c-form-fieldset">
        <legend>自動アンフォロー関連</legend>
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
        <div class="c-form-group">
          <label for="email" class="c-form-group__label">・いいねキーワード</label>
          <string-list-manager v-model="favoriteKeywordArray" :placeholder="'例）プログラミング'"></string-list-manager>
        </div>
      </fieldset>

      <div class="c-justify-content-end">
        <button class="c-btn c-btn--primary c-btn--large u-mr-2" @click="saveSetting">保存</button>
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
      msgDaysUnfollowUser: ""
    };
  },
  methods: {
    onChangeAccount: function(id) {
      // 操作中のアカウント変更時に、アカウントの設定情報をDBから取得する
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
        })
        .catch(error => {
          this.isError = true;
        });
    },
    saveSetting: function() {
      this.setting.days_unfollow_user === 0 ||
      this.setting.days_unfollow_user > 999
        ? (this.msgDaysUnfollowUser = "1~999を入力してください")
        : (this.msgDaysUnfollowUser = "");

      if (this.msgDaysUnfollowUser !== "") {
        // エラーがある場合は保存処理しない
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
          this.flash("設定を保存しました", "success", {
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
          })
          .catch(error => {
            this.isError = true;
          });
      })
      .catch(error => {
        this.isError = true;
      });
  }
};
</script>
