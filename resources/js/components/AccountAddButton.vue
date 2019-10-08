<template>
  <div>
    <button class="c-btn c-btn--primary" @click="addAccount">
      <i class="fas fa-user-plus"></i>アカウント追加
    </button>
    <span class="c-invalid-feedback">{{msg}}</span>
  </div>
</template>

<script>
export default {
  data: function() {
    return {
      msg: ""
    };
  },
  methods: {
    addAccount: function() {
      // １ユーザーが登録できるTwitterアカウントの最大数
      const MAX_ACCOUNT_NUM = 10;

      axios
        .get("/account/count")
        .then(res => {
          if (res.data >= MAX_ACCOUNT_NUM) {
            this.msg = "アカウントをこれ以上追加できません";
          } else {
            document.location = "/account/add";
          }
        })
        .catch(error => {
          this.msg =
            "正常に処理できませんでした。しばらく経ってからもう一度お試しください。";
        });
    }
  }
};
</script>