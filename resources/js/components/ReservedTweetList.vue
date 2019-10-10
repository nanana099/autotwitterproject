<template>
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
    deleteTweet: function(tweet) {
      if (!window.confirm("ツイートの予約を削除しますか？")) {
        return;
      }
      axios
        .delete("/account/tweet", {
          params: {
            account_id: localStorage.selectedId,
            id: tweet.id
          }
        })
        .then(res => {
          var index = this.tweets.indexOf(tweet);
          // key番目から１つ削除
          this.tweets.splice(index, 1);

          this.flash("ツイートを削除しました", "success", {
            timeout: 5000,
            important: true
          });
        })
        .catch(error => {
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