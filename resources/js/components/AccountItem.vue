<template>
  <li class="p-monitor-list__item">
    <div class="p-monitor-list__account-info">
      <img :src="account.profile_image_url_https" alt="アカウントアイコン" class="p-monitor-list__img" />
      <span class="p-monito-list__user-name">{{account.screen_name}}</span>
    </div>
    <div class="p-monitor-list__buttons">
      <delete-account-button
        :account="account"
        @deleteAccount="function(account){$emit('deleteAccount',account)}"
      ></delete-account-button>
    </div>
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