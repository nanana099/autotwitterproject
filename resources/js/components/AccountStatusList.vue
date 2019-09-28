<template>
  <ul class="p-monitor-list">
    <account-status
      v-for="accountStatus in accountStatuses"
      :key="accountStatus.id"
      :accounsStatus="accountStatus"
    ></account-status>
  </ul>
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
        console.log(res.data);
        this.accountStatuses = res.data;
      })
      .catch(error => {});
  }
};
</script>