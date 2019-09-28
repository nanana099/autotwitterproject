<template>
  <li class="p-monitor-list__item">
    <div class="p-monitor-list__account-info">
      <img :src="accounsStatus.image_url" alt class="p-monitor-list__img" />
      <span class="p-monito-list__user-name">{{accounsStatus.screen_name}}</span>
    </div>
    <div class="p-monitor-list__buttons">
      <div class="p-monitor-list__form-group">
        <label for class="p-monitor-list__form-label">フォロー</label>
        <button
          class="c-btn c-btn--primary"
          v-if="accounsStatus.operation_status.is_follow"
          @click="stopFollow"
        >稼働中</button>
        <button class="c-btn" v-else @click="startFollow">停止済</button>
      </div>

      <div class="p-monitor-list__form-group">
        <label for class="p-monitor-list__form-label">アンフォロー</label>
        <button
          class="c-btn c-btn--primary"
          v-if="accounsStatus.operation_status.is_unfollow"
          @click="stopUnfollow"
        >稼働中</button>
        <button class="c-btn" v-else @click="startUnfollow">停止済</button>
      </div>

      <div class="p-monitor-list__form-group">
        <label for class="p-monitor-list__form-label">いいね</label>
        <button
          class="c-btn c-btn--primary"
          v-if="accounsStatus.operation_status.is_favorite"
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
    stopFollow: function() {
      this.changeStatus("follow", false, () => {
        this.accounsStatus.operation_status.is_follow = false;
      });
    },
    startFollow: function() {
      this.changeStatus("follow", true, () => {
        this.accounsStatus.operation_status.is_follow = true;
      });
    },
    stopUnfollow: function() {
      this.changeStatus("unfollow", false, () => {
        this.accounsStatus.operation_status.is_unfollow = false;
      });
    },
    startUnfollow: function() {
      this.changeStatus("unfollow", true, () => {
        this.accounsStatus.operation_status.is_unfollow = true;
      });
    },
    stopFavorite: function() {
      this.changeStatus("favorite", false, () => {
        this.accounsStatus.operation_status.is_favorite = false;
      });
    },
    startFavorite: function() {
      this.changeStatus("favorite", true, () => {
        this.accounsStatus.operation_status.is_favorite = true;
      });
    },
    changeStatus: function(type, value, callback) {
      axios
        .post("/account/status", {
          operation_status_id: this.accounsStatus.operation_status.id,
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
  props: ["accounsStatus"]
};
</script>