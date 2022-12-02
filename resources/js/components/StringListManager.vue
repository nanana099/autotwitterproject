<template>
  <!-- 文字列を複数選択可能セレクトボックスに表示・登録・削除するための部品 -->
  <div class="p-strlist u-mb-2">
    <span class="c-invalid-feedback" v-if="errorMsg">{{errorMsg}}</span>

    <div class="c-align-item-start u-mb-1">
      <input type="text" class="p-strlist__text-box" v-model="addStr" :placeholder="placeholder" />
      <button class="c-btn c-btn--primary" @click="addTarget">&#043;</button>
    </div>
    <div class="c-align-item-end">
      <select size="5" class="p-strlist__select-box" v-model="selectedStr" multiple>
        <option v-for="target in ary" :value="target" :key="target">{{target}}</option>
      </select>
      <button class="c-btn c-btn--danger" @click="deleteTarget">&#045;</button>
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
      errorMsg: ""
    };
  },
  props: ["value", "placeholder", "maxLength", "maxCount"], // value:参照元からセレクトボックスに表示する配列を受け取る maxLength:文字列最大長 maxCount:文字列の個数最大
  watch: {
    value() {
      this.ary = this.value;
    }
  },
  methods: {
    // 配列に要素を追加
    addTarget: function() {
      // バリデーション
      if (this.addStr === "") {
        return;
      }
      if (this.addStr.match(",")) {
        this.errorMsg = "','は含めません";
        return;
      }
      if (this.addStr.match(" ")) {
        this.errorMsg = "' 'は含めません";
        return; 
      }
      if (this.addStr.match('\\(')) {
        this.errorMsg = "'('は含めません";
        return;
      }
      if (this.addStr.match('\\)')) {
        this.errorMsg = "')'は含めません";
        return;
      }
      if (this.addStr.length > this.maxLength) {
        this.errorMsg = "文字列が長すぎます";
        return;
      }
      if (this.ary.length >= this.maxCount) {
        this.errorMsg = "これ以上追加できません";
        return;
      }
      this.errorMsg = "";

      // 要素の追加処理
      if (!this.ary.some(x => x === this.addStr)) {
        this.ary.push(this.addStr);
        this.addStr = "";
      } else {
        this.errorMsg = "すでに追加済みです";
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