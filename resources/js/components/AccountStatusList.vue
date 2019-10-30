<template>
  <!-- 自動機能稼働状況の一覧 -->
  <div>
    <h2 class="c-title">アカウント一覧・稼働状況</h2>
    <section class="p-section">
      <div v-show="isLoading">
        <span class="p-message-1">
          Loading...
        </span>
      </div>
      <div v-show="!isLoading">
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
      accountStatuses: [],
      isLoading : true
    };
  },
  created: function() {
    // ログイン中のユーザーに紐づくTwitterアカウントを全件取得する
    axios
      .get("/account/status")
      .then(res => {
        this.accountStatuses = res.data;
        this.isLoading = false;
      })
      .catch(error => {
        // Todo:なんか表示する
      });
  },
  computed: {
    // Twitterアカウントが１件以上登録されているか
    existsAccount: function() {
      return this.accountStatuses.length > 0;
    }
  }
};
</script>