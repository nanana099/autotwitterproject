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
  </div>
  <!-- <form action="">
        <fieldset class="c-form-fieldset">
            <legend>自動フォロー関連</legend>
            <div class="c-form-group">
                <label for="email" class="c-form-group__label">・フォローキーワード</label>
                <input id="email" type="email"
                    class="c-form-group__text form-control" name="email"
                    value="" required autocomplete="email" autofocus>

                <span class="c-invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>

            </div>
            <div class="c-form-group">
                <label for="email" class="c-form-group__label">・ターゲットアカウント</label>
                <select id="list2" name="list2" size="5" class="c-form-group__select-multi u-mb-3" multiple>
                    <option value="1">abc_iii</option>
                    <option value="1">efafeafeee</option>
                </select>
                <div class="c-justify-content-end">
                    <label for="">追加するアカウント名：
                        <input id="email" type="email" class=" form-control"
                            name="email" value="" required autocomplete="email" autofocus></label>
                    <button class="c-btn c-btn--primary">追加</button>
                    <button class="c-btn c-btn--danger">削除</button>
                </div>
            </div>
        </fieldset>
        <fieldset class="c-form-fieldset">
            <legend>自動アンフォロー関連</legend>
            <div class="c-form-group">
                <label for="email" class="">・フォローしてから
                    <input id="" type="number" class=" form-control" name="email"
                        value="" required autocomplete="email" autofocus>
                    日間、フォローが無かったらアンフォローする</label>

                <span class="c-invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>

            </div>

            <div class="c-form-group">
                <label for="unfollow-inactive" class="">
                    ・<input type="checkbox" name="unfollow-inactive" id="unfollow-inactive">
                    非アクティブのユーザーのフォローを外す
                </label>
            </div>
        </fieldset>
        <fieldset class="c-form-fieldset">
            <legend>自動いいね関連</legend>
            <div class="c-form-group">
                <label for="email" class="c-form-group__label">・いいねキーワード</label>
                <input id="email" type="email"
                    class="c-form-group__text form-control" name="email"
                    value="" required autocomplete="email" autofocus>

                <span class="c-invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>

            </div>
        </fieldset>

        <div class="c-justify-content-end">
            <button class="c-btn c-btn--primary c-btn--large u-mr-2">保存</button>
        </div>
  </form>-->
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
      axios
        .get("/account/setting", {
          params: {
            account_id: id
          }
        })
        .then(res => {
          this.setting = res.data[0];
          this.targetAccountArray = res.data[0].target_accounts.split(",");
          this.followKeywordArray = res.data[0].keyword_follow.split(",");
          this.favoriteKeywordArray = res.data[0].keyword_favorite.split(",");
        })
        .catch(error => {
          this.isError = true;
        });
    },
    saveSetting: function() {
      if (
        this.setting.days_unfollow_user === 0 ||
        this.setting.days_unfollow_user > 999
      ) {
        this.msgDaysUnfollowUser = "1~999を入力してください";
      } else {
        this.msgDaysUnfollowUser = "";
      }
      if (this.msgDaysUnfollowUser !== "") {
        return;
      }
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
        .then()
        .catch();
    }
  },
  created: function() {
    axios
      .get("/account/get", {})
      .then(res => {
        this.accounts = res.data;
        let targetId;
        if (true) {
          // 選択中のアカウントがある
          targetId = localStorage.selectedId;
        } else {
          // 選択中のアカウントがない
          targetId = this.accounts[0]["id"];
        }
        axios
          .get("/account/setting", {
            params: {
              account_id: targetId
            }
          })
          .then(res => {
            this.setting = res.data[0];
            this.targetAccountArray = res.data[0].target_accounts.split(",");
            this.followKeywordArray = res.data[0].keyword_follow.split(",");
            this.favoriteKeywordArray = res.data[0].keyword_favorite.split(",");
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