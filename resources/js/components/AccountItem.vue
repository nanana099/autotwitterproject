<template>
  <!-- アカウント情報表示用のカード -->
  <li class="p-monitor-list__item">
    <div class="p-monitor-list__account-info">
      <img :src="account.image_url" alt="アカウントアイコン" class="p-monitor-list__img" />
      <span class="p-monito-list__user-name">{{account.screen_name}}</span>
    </div>
    <delete-account-button
      :account="account"
      @deleteAccount="function(account){$emit('deleteAccount',account)}"
    ></delete-account-button>
  </li>
</template>

<script>
import DeleteAccountButton from "./DeleteAccountButton";

export default {
  components: {
    "delete-account-button": DeleteAccountButton
  },
  props: ["account"]
};
</script>