<template>
  <div>
    <h2 class="c-title">稼働状況</h2>
    <section class="p-section">
      <ul class="p-monitor-list" v-if="existsAccount">
        <account-status
          v-for="accountStatus in accountStatuses"
          :key="accountStatus.id"
          :accounsStatus="accountStatus"
        ></account-status>
      </ul>
      <span class="p-message-1" v-else>
        <i class="fas fa-info-circle u-mr-2"></i>自動化したいTwitterアカウントを追加してください
      </span>
      <div class="c-justify-content-start">
        <account-add-button></account-add-button>
      </div>
    </section>
  </div>
</template>

<script>
import AccountStatus from "./AccounsStatus";
import AccountAddButton from "./AccountAddButton";

export default {
  components: {
    "account-status": AccountStatus,
    "account-add-button": AccountAddButton
  },
  data: function() {
    return {
      accountStatuses: []
    };
  },
  created: function() {
    // ログイン中のユーザーに紐づくTwitterアカウントを全件取得する
    axios
      .get("/account/status")
      .then(res => {
        this.accountStatuses = res.data;
      })
      .catch(error => {});
  },
  computed: {
    existsAccount: function() {
      return this.accountStatuses.length > 0;
    }
  }
};
</script>