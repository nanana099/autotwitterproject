<template>
  <!-- 年月日日時のセレクトボックス -->
  <div class="c-datetime-select-box">
    <div class="u-d-inline-block u-mb-1">
      <select name="year" v-model="year" v-on:change="modify" class="c-datetime-select-box__box1">
        <option v-for="year in getYears()" name="year" :value="year" :key="year">{{ year }}</option>
      </select>
      <label>&#047;</label>

      <select name="month" v-model="month" v-on:change="modify" class="c-datetime-select-box__box2">
        <option v-for="month in months" name="month" :value="month" :key="month">{{ month }}</option>
      </select>
      <label>&#047;</label>

      <select name="date" v-model="date" v-on:change="modify" class="c-datetime-select-box__box2">
        <option
          v-for="date in getDates(year, month)"
          name="date"
          :value="date"
          :key="date"
        >{{ date }}</option>
      </select>
      <label class="u-d-inline-blodk u-mr-1">&nbsp;</label>
    </div>

    <div class="u-d-inline-block u-mb-1">
      <select name="hour" v-model="hour" v-on:change="modify" class="c-datetime-select-box__box2">
        <option v-for="hour in hours" name="hour" :value="hour" :key="hour">{{ hour }}</option>
      </select>
      <label>&#058;</label>

      <select
        name="minute"
        v-model="minute"
        v-on:change="modify"
        class="c-datetime-select-box__box2"
      >
        <option v-for="minute in minutes" name="minute" :value="minute" :key="minute">{{ minute }}</option>
      </select>
    </div>
  </div>
</template>


<script>
import moment from "moment";

export default {
  props: ["value"],
  data: function() {
    return {
      datetime: this.value,
      months: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
      hours: [
        0,
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        10,
        11,
        12,
        13,
        14,
        15,
        16,
        17,
        18,
        19,
        20,
        21,
        22,
        23
      ],
      minutes: [
        0,
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        10,
        11,
        12,
        13,
        14,
        15,
        16,
        17,
        18,
        19,
        20,
        21,
        22,
        23,
        24,
        25,
        26,
        27,
        28,
        29,
        30,
        31,
        32,
        33,
        34,
        35,
        36,
        37,
        38,
        39,
        40,
        41,
        42,
        43,
        44,
        45,
        46,
        47,
        48,
        49,
        50,
        51,
        52,
        53,
        54,
        55,
        56,
        57,
        58,
        59
      ],
      year: this.value.year(),
      month: this.value.month() + 1,
      date: this.value.date(),
      hour: this.value.hour(),
      minute: this.value.minute()
    };
  },
  mounted: function() {
    this.init();
  },
  methods: {
    init: function() {
      this.year = this.value.year();
      this.month = this.value.month() + 1;
      this.date = this.value.date();
      this.hour = this.value.hour();
      this.minute = this.value.minute();
    },
    getYears: function() {
      const currentYear = moment().year();
      const nextYear = currentYear + 1;
      return [currentYear, nextYear];
    },
    getDates: function(year, month) {
      const maxDate = this.getFinalDate(year, month);
      return [...Array(maxDate).keys()].map(x => x + 1);
    },
    modify: function() {
      // 年や月が変更されたとき、日が存在しなくなる場合があるので調整する。
      // 例: 2018-12-31 を選択していて月が 12 から 2 に変更された場合、日を 28 にする。
      if (!moment([this.year, this.month - 1, this.date]).isValid()) {
        this.date = this.getFinalDate(this.year, this.month);
      }
      this.datetime
        .year(this.year)
        .month(this.month - 1)
        .date(this.date)
        .hour(this.hour)
        .minute(this.minute);
    },
    getFinalDate: function(year, month) {
      // 月末日
      return moment([year, month - 1])
        .endOf("month")
        .date();
    }
  }
};
</script>