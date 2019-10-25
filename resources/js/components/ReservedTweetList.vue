<template>
  <!-- 予約済みツイートの一覧 -->
  <div>
    <ul>
      <reserved-tweet
        v-for="(tweet,key) in tweets"
        :key="tweet.id"
        v-model="tweets[key]"
        @deleteTweet="deleteTweet"
      ></reserved-tweet>
    </ul>

    <flash-message class="p-flash_message--fixed"></flash-message>
  </div>
</template>

<script>
import ReservedTweet from "./ReservedTweet";
export default {
  components: {
    "reserved-tweet": ReservedTweet
  },
  data: function() {
    return {
      tweets: [] // 予約済みツイートの一覧
    };
  },
  mounted: function() {
    this.tweets = this.value;
  },
  created: function() {},
  methods: {
    // 予約済みツイートを削除
    deleteTweet: function(tweet) {
      // 確認用ダイアログを表示
      if (!window.confirm("ツイートの予約を削除しますか？")) {
        return;
      }
      // ajax
      axios
        .delete("/account/tweet", {
          params: {
            account_id: localStorage.selectedId,
            id: tweet.id
          }
        })
        .then(res => {
          // 成功
          var index = this.tweets.indexOf(tweet);
          // dataからも削除
          this.tweets.splice(index, 1);

          this.flash("ツイートを削除しました", "success", {
            timeout: 5000,
            important: true
          });
        })
        .catch(error => {
          // 失敗
          this.isError = true;

          this.flash(
            "ツイートの削除に失敗しました。しばらく経ってから再度お試しください。",
            "error",
            {
              timeout: 0,
              important: false
            }
          );
        });
    }
  },
  props: ["value"]
};
</script>