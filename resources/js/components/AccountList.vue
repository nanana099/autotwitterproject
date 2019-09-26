<template>
  <ul class="p-monitor-list">
    <account-item
      v-for="account in accounts"
      :account="account"
      :key="account.id"
      @deleteAccount="onDeleteAccount"
    ></account-item>
  </ul>
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
      if (!window.confirm("アカウントを削除しますか？")) {
        return;
      }
      axios
        .delete("/account/destroy", {
          params: {
            id: account.id
          }
        })
        .then(res => {
          console.log(res.data);
          if (!res.data["result"]) {
            console.log(this.accounts);
            var index = this.accounts.indexOf(account);
            // key番目から１つ削除
            this.accounts.splice(index, 1);
          }
        })
        .catch(error => {
          this.isError = true;
        });
    }
  },
  created: function() {
    axios
      .get("/account/get", {})
      .then(res => {
        this.accounts = res.data;
        console.log(this.accounts);
      })
      .catch(error => {
        this.isError = true;
      });
    //     $accounts = array(
    //       array(
    //           'id' => 1,
    //           'image' => 'https://iconbox.fun/wp/wp-content/uploads/106_h_24.svg',
    //           'screen_name' => 'たなか１'
    //       ),
    //       array(
    //           'id' => 2,
    //           'image' => 'https://iconbox.fun/wp/wp-content/uploads/106_h_24.svg',
    //           'screen_name' => 'たなか２'
    //       ),
    //   );
  },
  computed: {}
};
</script>
 