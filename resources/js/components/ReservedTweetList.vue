<template>
  <ul>
    <reserved-tweet
      v-for="(tweet,key) in tweets"
      :key="tweet.id"
      v-model="tweets[key]"
      @deleteTweet="deleteTweet"
    ></reserved-tweet>
  </ul>
</template>

<script>
import ReservedTweet from "./ReservedTweet";
export default {
  components: {
    "reserved-tweet": ReservedTweet
  },
  data: function() {
    return {
      tweets: []
    };
  },
  mounted: function() {
    this.tweets = this.value;
  },
  created: function() {},
  watch: {
    value() {
      this.choice = this.value.choice;
      this.text = this.value.text;
    }
  },
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
        })
        .catch(error => {
          this.isError = true;
        });
    }
  },
  props: ["value"]
};
</script>