<template>
  <!-- アカウント一覧 -->
  <div>
    <div v-show="isLoading">
      <span class="p-message-1">Loading...</span>
    </div>
    <div v-show="!isLoading">
      <ul class="p-monitor-list" v-if="existsAccount">
        <account-item
          v-for="account in accounts"
          :account="account"
          :key="account.id"
          @deleteAccount="onDeleteAccount"
        ></account-item>
      </ul>
      <span class="p-message-1" v-else>
        <i class="fas fa-info-circle u-mr-2"></i>Twitterアカウントが登録されていません。
        <a href="/mypage/monitor">アカウント一覧</a>から登録してください。
      </span>
    </div>
    <flash-message class="p-flash_message--fixed"></flash-message>
  </div>
</template>

<script>
import AccountItem from "./AccountItem";

export default {
  name: "AccountList",
  components: {
    "account-item": AccountItem
  },
  data: function() {
    return {
      accounts: [],
      isLoading: true
    };
  },
  methods: {
    // Twitterアカウントの削除
    onDeleteAccount: function(account) {
      // ダイアログで確認
      if (
        !window.confirm(
          "神ったーから、アカウント情報を全て削除します。\n一度削除すると、復元ができません。\nアカウントを削除しますか？"
        )
      ) {
        return;
      }
      // 子コンポーネントから渡ってきたアカウントをDBから削除する
      axios
        .delete("/account/destroy", {
          params: {
            id: account.id
          }
        })
        .then(res => {
          // 成功
          if (!res.data["result"]) {
            var index = this.accounts.indexOf(account);
            // dataからも削除
            this.accounts.splice(index, 1);
          }
          this.flash("アカウントを削除しました", "success", {
            timeout: 5000,
            important: true
          });
        })
        .catch(error => {
          // 失敗
          this.isError = true;
          this.flash(
            "アカウントの削除に失敗しました。しばらく経ってから再度お試しください。",
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
    // 画面表示時
    // アカウント一覧取得
    axios
      .get("/account/get", {})
      .then(res => {
        this.accounts = res.data;
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
  },
  computed: {
    existsAccount: function() {
      return this.accounts.length > 0;
    }
  }
};
</script>
 