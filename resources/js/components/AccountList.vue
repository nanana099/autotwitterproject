<template>
  <div>
    <ul class="p-monitor-list" v-if="existsAccount">
      <account-item
        v-for="account in accounts"
        :account="account"
        :key="account.id"
        @deleteAccount="onDeleteAccount"
      ></account-item>
    </ul>
    <span class="p-message-1" v-else>
      <i class="fas fa-info-circle u-mr-2"></i>Twitterアカウントが登録されていません
    </span>
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
      accounts: []
    };
  },
  methods: {
    onDeleteAccount: function(account) {
      if (
        !window.confirm(
          "神ったーから、アカウント情報を全て削除します。\n一度削除すると、復元ができません。\nアカウントを削除しますか？"
        )
      ) {
        return;
      }
      axios
        .delete("/account/destroy", {
          params: {
            id: account.id
          }
        })
        .then(res => {
          // 子コンポーネントから渡ってきたアカウントをDBから削除する
          if (!res.data["result"]) {
            var index = this.accounts.indexOf(account);
            this.accounts.splice(index, 1);
          }
        })
        .catch(error => {
          this.isError = true;
        });
    }
  },
  created: function() {
    // アカウント一覧取得
    axios
      .get("/account/get", {})
      .then(res => {
        this.accounts = res.data;
      })
      .catch(error => {
        this.isError = true;
      });
  },
  computed: {
    existsAccount: function() {
      return this.accounts.length > 0;
    }
  }
};
</script>
 