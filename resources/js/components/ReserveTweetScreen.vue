<template>
  <div>
    <section class="p-section">
      <div class="p-select-account">
        <label for class="p-select-account__label">
          操作中のアカウント：
          <account-select-box :accounts="accounts" @changeAccount="onChangeAccount"></account-select-box>
        </label>
      </div>
      <h2 class="c-title">自動ツイート予約</h2>
      <reserve-tweet-form @addedTweet="addTweetList"></reserve-tweet-form>
    </section>
    <section class="p-section">
      <h2 class="c-title">予約済みツイート</h2>
      <reserved-tweet-list v-model="tweets"></reserved-tweet-list>
    </section>
  </div>
</template>

<script>
import AccountSelectBox from "./AccountSelectBox";
import ReserveTweetForm from "./ReserveTweetForm";
import ReservedTweetList from "./ReservedTweetList";

export default {
  components: {
    "account-select-box": AccountSelectBox,
    "reserve-tweet-form": ReserveTweetForm,
    "reserved-tweet-list": ReservedTweetList
  },
  data: function() {
    return {
      accounts: [],
      tweets: []
    };
  },
  methods: {
    onChangeAccount: function() {
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
          axios
            .get("/account/tweet", {
              params: {
                account_id: targetId
              }
            })
            .then(res => {
              // this.tweets = res.data;

              // key番目から１つ削除
              this.tweets.splice(0, this.tweets.length);

              res.data.forEach(e => {
                this.tweets.push(e);
              });
            })
            .catch(error => {
              this.isError = true;
            });
        })
        .catch(error => {
          this.isError = true;
        });
    },
    addTweetList: function(tweet) {
      this.tweets.push({
        content: tweet.content,
        submit_date: tweet.submit_date,
        id: tweet.id
      });
    }
  },
  computed: {
    start() {
      // min-date に明日の9時を指定
      const start = moment();
      return start.format("YYYY-MM-DDTHH:mm:ss");
    },
    end() {
      // max-date に min-date から3ヶ月後を指定
      const start = moment(this.start);
      const end = start.add(3, "months").endOf("day");
      return end.format("YYYY-MM-DDTHH:mm:ss");
    }
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
        axios
          .get("/account/tweet", {
            params: {
              account_id: targetId
            }
          })
          .then(res => {
            // this.tweets = res.data;
            res.data.forEach(e => {
              this.tweets.push(e);
            });
          })
          .catch(error => {
            this.isError = true;
          });
      })
      .catch(error => {
        this.isError = true;
      });
  }
};
</script>