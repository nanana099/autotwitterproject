<template>
  <div>
    <textarea v-model="content" id class="p-tweet-form__textarea" placeholder="つぶやき内容" />
    <div class="c-justify-content-between mb-2 c-align-item-start">
      <div class="c-justify-content-start">
        <label>投稿予定日時：</label>
        <input type="datetime-local" v-model="requestDate" :min="start" :max="end" />
        <span class="c-invalid-feedback">{{msg}}</span>
      </div>
      <span class="p-tweet-form__count">
        <span :class="{'c-invalid-feedback':isOverContent}">{{count}}/140字</span>
      </span>
    </div>
    <div class="c-justify-content-end">
      <button class="c-btn c-btn--primary c-btn--large" @click="reserveTweet">予約</button>
    </div>
  </div>
</template>

<script>
import moment from "moment";

export default {
  data: function() {
    return {
      content: "",
      requestDate: "",
      id: "",
      msg: ""
    };
  },
  mounted: function() {
    if (this.tweet) {
      this.content = this.tweet.content;
      this.requestDate = moment(this.tweet.submit_date).format(
        "YYYY-MM-DDTHH:mm"
      );
      this.id = this.tweet.id;
    }
  },
  methods: {
    reserveTweet: function() {
      if (!this.validTweet()) return;
      axios
        .post("/account/tweet", {
          content: this.content,
          submit_date: this.requestDate,
          account_id: localStorage.selectedId,
          reserved_tweet_id: this.id
        })
        .then(res => {
          let content = this.content;
          let submit_date = moment(this.requestDate).format("YYYY-MM-DD HH:mm");

          this.content = "";
          this.requestDate = "";
          this.$emit("addedTweet", {
            content: content,
            submit_date: submit_date,
            id: res.data.id
          });
        })
        .catch(error => {
          this.isError = true;
        });
    },
    validTweet: function() {
      if (this.content.length === 0 && this.content.length > 140) {
        return false;
      }
      if (this.requestDate === "") {
        this.msg = "日時の入力は必須です";
        return false;
      }
      this.msg = "";

      return true;
    }
  },
  computed: {
    start: function() {
      // min-date に明日の9時を指定
      const start = moment();
      return start.format("YYYY-MM-DDTHH:mm");
    },
    end: function() {
      // max-date に min-date から3ヶ月後を指定
      const start = moment(this.start);
      const end = start.add(1, "years").endOf("day");
      return end.format("YYYY-MM-DDTHH:mm");
    },
    count: function() {
      return this.content.length;
    },
    isOverContent: function() {
      return this.content.length > 140;
    }
  },
  props: ["tweet"]
};
</script>