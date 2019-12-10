<template>
  <!-- 自動機能稼働状況の一覧 -->
  <div>
    <h2 class="c-title">アカウント一覧・稼働状況</h2>
    <section class="p-section">
      <div v-show="isLoading">
        <span class="p-message-1">Loading...</span>
      </div>
      <div v-show="!isLoading">
        <ul class="p-monitor-list" v-if="existsAccount">
          <account-status
            v-for="accountStatus in accountStatuses"
            :key="accountStatus.id"
            :accountStatus="accountStatus"
          ></account-status>
        </ul>
        <span class="p-message-1" v-else>
          <i class="fas fa-info-circle u-mr-2"></i>自動化したいTwitterアカウントを追加してください
        </span>
        <div class="c-justify-content-start u-mb-6">
          <account-add-button></account-add-button>
        </div>

        <div class="u-border u-p-1">
          <div class="u-mb-1">
            <span>※自動機能について</span>
          </div>
          <p class="u-fs-4">
            「稼働中」の機能を、10~20分程度の間隔で実行します。
            <br />一度に処理できる量には限りがあるため、複数回にわけて実行されます。
            <br />各機能の完了時に登録済みのメールアドレスへご連絡いたします。
            <br />
            <br />なお、「過剰フォロー・フォロー解除」などのTwitterルールに抵触した場合、みなさまのTwitterアカウントが凍結したり、神ったー自体の機能が停止する可能性があります。
            <br />それを避けるため、独自の基準でアクションする回数を制限しておりますので、自動機能が動作が一時中断する可能性があることを、ご了承ください。
          </p>
        </div>
      </div>
    </section>
    <flash-message class="p-flash_message--fixed"></flash-message>
  </div>
</template>

<script>
import AccountStatus from "./AccountStatus";
import AccountAddButton from "./AccountAddButton";

export default {
  components: {
    "account-status": AccountStatus,
    "account-add-button": AccountAddButton
  },
  data: function() {
    return {
      accountStatuses: [],
      isLoading: true
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
    // Twitterアカウントが１件以上登録されているか
    existsAccount: function() {
      return this.accountStatuses.length > 0;
    }
  }
};
</script>