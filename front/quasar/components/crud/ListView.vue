<template>
    <div class="q-pa-md">
      <q-table v-if="config"
      :title="config.title"
      :data="data"
      :columns="columns"
      row-key="name"
      :pagination="{ rowsPerPage: config.listView.items_per_page}"
    >
      <slot v-for="(_, name) in $slots" :name="name" :slot="name" />
      <template v-for="(_, name) in $scopedSlots" :slot="name" slot-scope="slotData"><slot :name="name" v-bind="slotData" /></template>
    </q-table>

  </div>
</template>

<script>

export default {
  props: {
    'crudkey': {required: true}
  },
  computed: {
    config() { return this.$store.getters['crud/getCrudConfig'](this.crudkey) },
    columns() { return this.$store.getters['crud/getListColumn'](this.crudkey) },
    data() { return this.$store.getters['provider/getItems']()}
  },
  methods: {},
  async created() {
    await this.$store.dispatch('crud/FetchCrudConfig', this.crudkey)
    let config = this.$store.getters['crud/getCrudConfig'](this.crudkey);
    this.$store.dispatch('provider/loadItems', config)

  }
}
</script>
