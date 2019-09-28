<template>
  <div>
    <ul class="p-monitor-list"  v-if="existsAccount">
      <account-status
        v-for="accountStatus in accountStatuses"
        :key="accountStatus.id"
        :accounsStatus="accountStatus"
      ></account-status>
    </ul>
    <span class="p-message-1" v-else><i class="fas fa-info-circle u-mr-2"></i>自動化したいTwitterアカウントを追加してください</span>
  </div>
</template>

<script>
import AccountStatus from "./AccounsStatus";
export default {
  components: {
    "account-status": AccountStatus
  },
  data: function() {
    return {
      accountStatuses: []
    };
  },
  created: function() {
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