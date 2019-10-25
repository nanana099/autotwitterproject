<template>
  <!-- アカウント選択のセレクトボックス -->
  <select
    v-model="selectedId"
    options="accounts"
    class="p-select-account__select"
    @change="$emit('changeAccount', selectedId)"
  >
    <option disabled value="">選択してください</option>
    <option
      v-for="account in accounts"
      :key="account.id"
      :value="account.id"
    >{{account.screen_name}}</option>
  </select>
</template>

<script>
export default {
  data: function() {
    return {
      selectedId: ""
    };
  },
  props: ["accounts"],
  mounted() {
    // 前回選択していたTwitterアカウントをセレクトボックス上選択する
    if (localStorage.selectedId) {
      this.selectedId = localStorage.selectedId;
    }
  },
  watch: {
    selectedId(newId) {
      localStorage.selectedId = newId;
    }
  }
};
</script>