<template>
  <div>
    <div class="u-m-2">
      <input type="text" class="c-textbox--small" v-model="addStr" :placeholder="placeholder" />
      <button class="c-btn c-btn--primary" @click="addTarget">追加</button>
      <span class="c-invalid-feedback">{{msg}}</span>
    </div>
    <select size="5" class="c-form-group__select-multi u-mb-3" v-model="selectedStr" multiple>
      <option v-for="target in ary" :value="target" :key="target">{{target}}</option>
    </select>
    <div class="c-justify-content-end">
      <button class="c-btn c-btn--danger" @click="deleteTarget">削除</button>
    </div>
  </div>
</template>

<script>
export default {
  data: function() {
    return {
      ary: [], // セレクトボックス内の文字列の配列
      addStr: "", // 追加用テキストボックス内の文字列
      selectedStr: [], // セレクトボックスで選択中要素の配列
      msg: ""
    };
  },
  props: ["value","placeholder"], // 参照元からセレクトボックスに表示する配列を受け取る
  watch: {
    value() {
      this.ary = this.value;
    }
  },
  methods: {
    // 配列に要素を追加
    addTarget: function() {
      if (this.addStr === "") {
        return;
      }
      if (this.addStr.match(",")) {
        this.msg = "','を含むことはできません";
        return;
      }
      this.msg = "";

      if (!this.ary.some(x => x === this.addStr)) {
        this.ary.push(this.addStr);
        this.addStr = "";
      }
    },
    // 配列から要素を削除
    deleteTarget: function() {
      this.selectedStr.forEach(deleteItem => {
        this.ary.forEach((item, index) => {
          if (item === deleteItem) {
            this.ary.splice(index, 1);
          }
        });
      });
    }
  }
};
</script>