<template>
  <!-- 予約ツイート編集用フォーム -->
  <div>
    <textarea v-model="content" id class="p-tweet-form__textarea" placeholder="つぶやき内容" />

    <div class="c-justify-content-between c-align-item-end u-mb-1">
      <label>投稿時刻：</label>
      <div class="p-tweet-form__count">
        <span :class="{'c-invalid-feedback':isOverContent}">{{count}}/140字</span>
      </div>
    </div>
    <div class="c-justify-content-between u-mb-2">
      <div class="c-justify-content-start">
        <datetime-select-box v-model="requestDate" ref="datetimeSelectBox"></datetime-select-box>
        <span class="c-invalid-feedback">{{errorMsgDatetime}}</span>
      </div>
    </div>

    <div class="c-justify-content-end">
      <button class="c-btn c-btn--primary c-btn--large" @click="reserveTweet">予約</button>
    </div>
    <flash-message class="p-flash_message--fixed"></flash-message>
  </div>
</template>

<script>
import moment from "moment";

import DateTimeSelectBox from "./DateTimeSelectBox";
export default {
  props: ["tweet"],
  components: {
    "datetime-select-box": DateTimeSelectBox
  },
  data: function() {
    return {
      content: "",
      requestDate: moment(),
      id: "",
      errorMsgDatetime: ""
    };
  },
  beforeMount: function() {
    if (this.tweet) {
      // 予約済みツイート
      this.content = this.tweet.content;
      let date = moment(this.tweet.submit_date);
      this.requestDate
        .year(date.year())
        .month(date.month())
        .date(date.date())
        .hour(date.hour())
        .minute(date.minute());

      // this.requestDate = moment(this.tweet.submit_date).format(
      //   "YYYY-MM-DD HH:mm:ss"
      // );
      this.id = this.tweet.id;
    } else {
      // 新規
      this.requestDate.add(1, "days");
      // this.requestDate = moment()
      //   .add(1, "days")
      //   .format("YYYY-MM-DD HH:mm:ss");
    }
  },
  methods: {
    reserveTweet: function() {
      // ツイート予約をDBへ更新または挿入する
      if (!this.validTweet()) return;

      // ajax
      axios
        .post("/account/tweet", {
          content: this.content,
          submit_date: this.requestDate.format("YYYY-MM-DD HH:mm"),
          account_id: localStorage.selectedId,
          reserved_tweet_id: this.id
        })
        .then(res => {
          // 成功
          let content = this.content;
          let submit_date = this.requestDate.format("YYYY-MM-DD HH:mm");

          // フォームの表示をクリア
          this.formInit();

          // Tweetが追加された時のイベントを親に通知する
          this.$emit("addedTweet", {
            content: content,
            submit_date: submit_date,
            id: res.data.id
          });

          this.flash("ツイートを予約しました", "success", {
            timeout: 5000,
            important: true
          });
        })
        .catch(error => {
          // 失敗
          this.flash(
            "ツイートを予約に失敗しました。しばらく経ってから再度お試しください。",
            "error",
            {
              timeout: 0,
              important: false
            }
          );
        });
    },
    formInit: function() {
      // フォームの表示をクリア
      this.content = "";
      let date = moment();
      this.requestDate
        .year(date.year())
        .month(date.month())
        .date(date.date() + 1)
        .hour(date.hour())
        .minute(date.minute());
      this.$refs.datetimeSelectBox.init();
    },
    validTweet: function() {
      // 日付日時
      if (this.requestDate === "") {
        this.errorMsgDatetime = "日時の入力は必須です";
        return false;
      }
      if (this.requestDate.isBefore(moment())) {
        this.errorMsgDatetime = "現在日時よりも後の日時を入力してください";
        return false;
      }
      this.errorMsgDatetime = "";

      // Tweet内容
      if (this.content.length === 0 || this.content.length > 140) {
        return false;
      }

      return true;
    }
  },
  computed: {
    start: function() {
      // 日付け用
      const start = moment();
      return start.format("YYYY-MM-DDTHH:mm");
    },
    end: function() {
      // 日付け用
      const start = moment(this.start);
      const end = start.add(1, "years").endOf("day");
      return end.format("YYYY-MM-DDTHH:mm");
    },
    count: function() {
      // 文字数用
      return this.content.length;
    },
    isOverContent: function() {
      // 文字数用
      return this.content.length > 140;
    }
  }
};
</script>