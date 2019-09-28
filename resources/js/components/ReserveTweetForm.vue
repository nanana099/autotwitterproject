<template>
  <div>
    <textarea v-model="content" id class="p-tweet-form__textarea" />
    <div class="c-justify-content-between mb-2 c-align-item-start">
      <div class="c-justify-content-start">
        <label>投稿予定日時</label>
        <vue-ctkc-date-time-picker
          v-model="requestDate"
          :minute-interval="1"
          :format="'YYYY-MM-DD HH:mm'"
          :overlay="true"
          :min-date="start"
        ></vue-ctkc-date-time-picker>
      </div>
      <span class="p-tweet-form__count js-show-count">140/140字</span>
    </div>
    <div class="c-justify-content-end">
      <button class="c-btn c-btn--primary c-btn--large" @click="reserveTweet">予約</button>
    </div>
  </div>
</template>

<script>
import moment from "moment";
import VueCtkDateTimePicker from "vue-ctk-date-time-picker";
import "vue-ctk-date-time-picker/dist/vue-ctk-date-time-picker.css";

export default {
  components: {
    "vue-ctkc-date-time-picker": VueCtkDateTimePicker
  },
  data: function() {
    return {
      content: "",
      requestDate: "",
      id: ""
    };
  },
  mounted: function() {
    if (this.tweet) {
      this.content = this.tweet.content;
      this.requestDate = this.tweet.submit_date;
      this.id = this.tweet.id;
    }
  },
  methods: {
    reserveTweet: function() {
      axios
        .post("/account/tweet", {
          content: this.content,
          submit_date: this.requestDate,
          account_id: localStorage.selectedId,
          reserved_tweet_id: this.id
        })
        .then(res => {
          let content = this.content;
          let submit_date = this.requestDate;

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
  created: function() {},
  props: ["tweet"]
};
</script>