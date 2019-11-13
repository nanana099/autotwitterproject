<template>
  <!-- ツイート予約画面 -->
  <div>
    <div class="p-select-account">
      <label for class="p-select-account__label">
        操作中のアカウント：
        <account-select-box :accounts="accounts" @changeAccount="onChangeAccount"></account-select-box>
      </label>
    </div>
    <section class="p-section">
      <h2 class="c-title">自動ツイート予約</h2>
      <div v-show="isLoading">
        <span class="p-message-1">Loading...</span>
      </div>
      <div v-show="!isLoading">
        <div v-show="!existsAccount">
          <span class="p-message-1">
            <i class="fas fa-info-circle u-mr-2"></i>Twitterアカウントが登録されていません。
            <a href="/mypage/monitor">アカウント一覧</a>から登録してください。
          </span>
        </div>
        <div v-show="existsAccount">
          <reserve-tweet-form @addedTweet="addTweetList"></reserve-tweet-form>
        </div>
      </div>
    </section>
    <section class="p-section">
      <h2 class="c-title">予約済みツイート</h2>

      <div v-show="isLoading">
        <span class="p-message-1">Loading...</span>
      </div>
      <div v-show="!isLoading">
        <div v-show="existsAccount">
          <reserved-tweet-list v-model="tweets"></reserved-tweet-list>
        </div>
      </div>
    </section>
    <flash-message class="p-flash_message--fixed"></flash-message>
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
      accounts: [], // アカウント一覧
      tweets: [], // Tweet一覧
      isLoading: true
    };
  },
  methods: {
    onChangeAccount: function() {
      // 選択中のTwitterアカウント変更時
      this.isLoading = true;
      // アカウント情報を取得
      axios
        .get("/account/get", {})
        .then(res => {
          this.accounts = res.data;
          let targetId;
          targetId = localStorage.selectedId;
          // 予約済みツイートを取得
          axios
            .get("/account/tweet", {
              params: {
                account_id: targetId
              }
            })
            .then(res => {
              // 成功
              this.tweets.splice(0, this.tweets.length);
              res.data.forEach(e => {
                this.tweets.push(e);
              });
              this.isLoading = false;
            })
            .catch(error => {
              // 失敗
              this.flash(
                "情報の取得に失敗しました。しばらく経ってから再度お試しください",
                "error",
                {
                  timeout: 0,
                  important: false
                }
              );
            });
        })
        .catch(error => {
          this.flash(
            "情報の取得に失敗しました。しばらく経ってから再度お試しください",
            "error",
            {
              timeout: 0,
              important: false
            }
          );
        });
    },
    addTweetList: function(tweet) {
      // Tweet一覧に新しいTweetを追加する
      this.tweets.push({
        content: tweet.content,
        submit_date: tweet.submit_date,
        id: tweet.id
      });
    }
  },
  created: function() {
    // アカウント一覧とTweet一覧を取得する
    axios
      .get("/account/get", {})
      .then(res => {
        this.accounts = res.data;
        let targetId;
        targetId = localStorage.selectedId;

        // Twitterアカウントが選択されているか？
        let isSelectedAccount = false;
        this.accounts.forEach(x => {
          if (x.id === Number(targetId)) {
            isSelectedAccount = true;
          }
        });
        if (!isSelectedAccount) {
          // 操作中のアカウントが未選択の場合
          this.flash("アカウントを選択してください", "info", {
            timeout: 0,
            important: false
          });
          this.isLoading = false;
          return;
        }

        // 予約済みツイート取得
        axios
          .get("/account/tweet", {
            params: {
              account_id: targetId
            }
          })
          .then(res => {
            res.data.forEach(e => {
              this.tweets.push(e);
            });
            this.isLoading = false;
          })
          .catch(error => {
            this.flash(
              "情報の取得に失敗しました。しばらく経ってから再度お試しください",
              "error",
              {
                timeout: 0,
                important: false
              }
            );
          });
      })
      .catch(error => {
        this.flash(
          "情報の取得に失敗しました。しばらく経ってから再度お試しください",
          "error",
          {
            timeout: 0,
            important: false
          }
        );
      });
  },
  computed: {
    // Twitterアカウントが１件以上登録されているか
    existsAccount: function() {
      return this.accounts.length > 0;
    }
  }
};
</script>