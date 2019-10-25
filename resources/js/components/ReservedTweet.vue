<template>
  <!-- １つの予約済みツイート表示用 -->
  <li class="p-reserve-history" style="list-style:none">
    <div v-if="!isEdit">
      <p class="p-reserve-history__content">{{tweet.content}}</p>
      <div class="c-justify-content-between">
        <span class="p-reserve-history__date">投稿予定日時：{{tweet.submit_date}}</span>
      </div>
    </div>
    <reserve-tweet-form v-if="isEdit" :tweet="tweet" @addedTweet="onEditCompleted" class="u-mb-2"></reserve-tweet-form>
    <div class="c-justify-content-end">
      <button class="c-btn c-btn--primary u-mr-2" @click="toEditMode" v-if="!isEdit">編集</button>
      <button class="c-btn c-btn--danger" v-if="!isEdit" @click="$emit('deleteTweet',value)">削除</button>
      <button class="c-btn c-btn--danger" v-if="isEdit" @click="canselEdit">キャンセル</button>
    </div>
  </li>
</template>

<script>
import ReserveTweetForm from "./ReserveTweetForm";
export default {
  components: {
    "reserve-tweet-form": ReserveTweetForm
  },
  data: function() {
    return {
      tweet: "",
      tmpTweet: "",
      isEdit: false
    };
  },
  mounted: function() {
    this.tweet = this.value; 
    this.tmpTweet = Object.create(this.value);
  },
  methods: {
    // 予約済みツイートを編集する
    toEditMode: function() {
      this.isEdit = true;
    },
    // 予約済みツイートの編集が完了した 
    onEditCompleted: function(tweet) {
      this.tweet.content = tweet.content;
      this.tweet.submit_date = tweet.submit_date;
      this.tmpTweet = Object.create(this.tweet);
      this.isEdit = false;
    },
    // 予約済みツイートの編集がキャンセルされた
    canselEdit: function() {
      this.isEdit = false;
      this.tweet.content = this.tmpTweet.content;
      this.tweet.submit_date = this.tmpTweet.submit_date;
    }
  },
  props: ["value"]
};
</script>