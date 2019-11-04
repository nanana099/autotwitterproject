<template>
  <!-- Twitterアカウントの自動機能稼働状況表示＆更新ボタン -->
  <li class="p-monitor-list__item">
    <div class="p-monitor-list__account-info">
      <img :src="accountStatus.image_url" alt class="p-monitor-list__img" />
      <span class="p-monito-list__user-name">{{accountStatus.screen_name}}</span>
    </div>
    <div class="p-monitor-list__buttons">
      <div class="p-monitor-list__form-group">
        <label for class="p-monitor-list__form-label">フォロー</label>
        <button
          class="c-btn c-btn--primary"
          v-if="accountStatus.operation_status.is_follow"
          @click="stopFollow"
        >稼働中</button>
        <button class="c-btn" v-else @click="startFollow">停止済</button>
      </div>

      <div class="p-monitor-list__form-group">
        <label for class="p-monitor-list__form-label">アンフォロー</label>
        <button
          class="c-btn c-btn--primary"
          v-if="accountStatus.operation_status.is_unfollow"
          @click="stopUnfollow"
        >稼働中</button>
        <button class="c-btn" v-else @click="startUnfollow">停止済</button>
      </div>

      <div class="p-monitor-list__form-group">
        <label for class="p-monitor-list__form-label">いいね</label>
        <button
          class="c-btn c-btn--primary"
          v-if="accountStatus.operation_status.is_favorite"
          @click="stopFavorite"
        >稼働中</button>
        <button class="c-btn" v-else @click="startFavorite">停止済</button>
      </div>
    </div>
  </li>
</template>

<script>
export default {
  date: function() {
    return {
      operation_status: []
    };
  },
  methods: {
    // 自動フォローをOFFにする
    stopFollow: function() {
      this.changeStatus("follow", false, () => {
        this.accountStatus.operation_status.is_follow = false;
      });
    },
    // 自動フォローをONにする
    startFollow: function() {
      this.changeStatus("follow", true, () => {
        this.accountStatus.operation_status.is_follow = true;
      });
    },
    // 自動アンフォローをOFFにする
    stopUnfollow: function() {
      this.changeStatus("unfollow", false, () => {
        this.accountStatus.operation_status.is_unfollow = false;
      });
    },
    // 自動アンフォローをONにする
    startUnfollow: function() {
      this.changeStatus("unfollow", true, () => {
        this.accountStatus.operation_status.is_unfollow = true;
      });
    },
    // 自動いいねをOFFにする
    stopFavorite: function() {
      this.changeStatus("favorite", false, () => {
        this.accountStatus.operation_status.is_favorite = false;
      });
    },
    // 自動いいねをONにする
    startFavorite: function() {
      this.changeStatus("favorite", true, () => {
        this.accountStatus.operation_status.is_favorite = true;
      });
    },
    // 稼働状況をDBに更新する
    changeStatus: function(type, value, callback) {
      // アカウントの自動機能稼働状況を更新する
      axios
        .post("/account/status", {
          operation_status_id: this.accountStatus.operation_status.id,
          type: type,
          value: value
        })
        .then(res => {
          callback();
        })
        .catch(error => {
        });
    }
  },
  props: ["accountStatus"]
};
</script>