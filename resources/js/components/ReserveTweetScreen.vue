<template>
  <div>
    <div class="p-select-account">
      <label for class="p-select-account__label">
        操作中のアカウント：
        <account-select-box :accounts="accounts" @changeAccount="onChangeAccount"></account-select-box>
      </label>
    </div>
    <h2 class="c-title">自動ツイート予約</h2>
    <div class="c-row">
      <form action class="p-tweet-form">
        <textarea name id class="p-tweet-form__textarea"></textarea>
        <span class="p-tweet-form__count js-show-count">140/140字</span>
        <div class="c-justify-content-between">
          <label for>
            投稿予定日時：
            <input type="date" name="date" id />
            <input type="time" name="time" id />
          </label>
          <button class="c-btn c-btn--primary c-btn--large">予約</button>
        </div>
      </form>

      <h2 class="c-title">予約済みツイート</h2>
      <div class="p-reserve-history">
        <form action>
          <p class="p-reserve-history__str">
            今日はいいてんきだ。
            <br />あしたもいいてんきにあるといいｆだいふぁ
          </p>
          <div class="c-justify-content-between">
            <span class="p-reserve-history__str">投稿予定日時：2019/09/12 13:00</span>
          </div>

          <div class="c-justify-content-end">
            <button class="c-btn c-btn--primary">編集</button>
            <button class="c-btn c-btn--danger">削除</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import AccountSelectBox from "./AccountSelectBox";
export default {
  components: {
    "account-select-box": AccountSelectBox
  },
  data: function() {
    return {
      accounts: [],
      content : '',
      date : '',
    };
  },
  created: function() {
    axios
      .get("/account/get", {})
      .then(res => {
        this.accounts = res.data;
        let targetId;
        if (true) {
          // 選択中のアカウントがある
          targetId = localStorage.selectedId;
        } else {
          // 選択中のアカウントがない
          targetId = this.accounts[0]["id"];
        }
        // axios
        //   .get("/account/setting", {
        //     params: {
        //       account_id: targetId
        //     }
        //   })
        //   .then(res => {
        //     this.setting = res.data[0];
        //     if (res.data[0].target_accounts !== "")
        //       this.targetAccounts = res.data[0].target_accounts.split(",");
        //   })
        //   .catch(error => {
        //     this.isError = true;
        //   });
      })
      .catch(error => {
        this.isError = true;
      });
  }
};
</script>