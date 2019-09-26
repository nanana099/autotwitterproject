<template>
  <button class="c-btn c-btn--danger" @click="deleteAccount">削除</button>
</template>

<script>
export default {
  methods: {
    deleteAccount: function() {
      axios
        .delete("/account/destroy", {
          params: {
            id: this.id
          }
        })
        .then(res => {
          console.log(res.data);
        })
        .catch(error => {
          this.isError = true;
        });
    }
  },
  props: ["id"]
};
</script>
 