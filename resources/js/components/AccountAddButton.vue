<template>
  <!--Twitterアカウント追加ボタン -->
  <div>
    <button class="c-btn c-btn--primary" @click="addAccount">
      <i class="fas fa-user-plus"></i>アカウント追加
    </button>
    <span class="c-invalid-feedback">{{errorMsg}}</span>
  </div>
</template>

<script>
export default {
  data: function() {
    return {
      errorMsg: ""
    };
  },
  methods: {
    // Twitterアカウントの追加
    addAccount: function() {
      // １ユーザーが登録できるTwitterアカウントの最大数
      const MAX_ACCOUNT_NUM = 10;

      // アカウントの追加(TwitterのOAuthページへリダイレクトする)
      axios
        .get("/account/count")
        .then(res => {
          if (res.data >= MAX_ACCOUNT_NUM) {
            this.errorMsg = "アカウントをこれ以上追加できません";
          } else {
            // リダイレクト
            document.location = "/account/add";
          }
        })
        .catch(error => {
          this.errorMsg =
            "正常に処理できませんでした。しばらく経ってからもう一度お試しください。";
        });
    }
  }
};
</script>