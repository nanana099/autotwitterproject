<template>
  <!-- Twitterアカウント削除ボタン -->
  <button class="c-btn c-btn--danger" @click="$emit('deleteAccount',account)">削除</button>
</template>

<script>
export default {
  props: ["account"]
};
</script>
 