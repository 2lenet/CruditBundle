<template>
  <q-layout view="lHh LpR lFf" v-if="getLayoutConfig">
    <q-header reveal class="header">
      <q-toolbar>
        <q-btn @click="left = !left" flat round dense icon="menu" class="q-mr-sm"/>
        <q-toolbar-title>{{ getLayoutConfig.design.mainTitle }}</q-toolbar-title>
        <q-space/>

        <!-- slot header -->
        <component v-for="comp in getLayoutConfig.components" v-if="comp.slot='header'" v-bind:is="comp.type" :options="comp.options"></component>
        <!-- end slot header -->

        <q-btn-dropdown flat label="Exploitation">
          <q-list bordered>
            <q-item v-close-popup>
              <q-item-section>
                <q-icon name="warehouse"/>
              </q-item-section>
              <q-item-section>Ferme des coquelicots</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>


        <q-space/>
        <q-btn class="q-mr-xs" flat round @click="$q.dark.toggle()"
               :icon="$q.dark.isActive ? 'nights_stay' : 'wb_sunny'"/>
        <q-btn flat round dense icon="logout" @click="logout" to="/"/>
      </q-toolbar>
    </q-header>

    <q-drawer class="left-navigation text-white" show-if-above v-model="left" side="left" elevated>
      <div class="full-height" :class="$q.dark.isActive ? 'drawer_dark' : 'drawer_normal'">
        <div style="height: calc(100% - 117px);padding:10px;">
          <q-toolbar>
            <q-toolbar-title>{{ getLayoutConfig.user.name }}</q-toolbar-title>
          </q-toolbar>
          <hr/>
          <q-scroll-area class="full-height">
            <q-list padding>
              <q-item v-for="item in getLayoutConfig.navigations.mainNav" class="q-ma-sm navigation-item" active-class="tab-active" :to="'/'+item.route" exact clickable v-ripple>
                <q-item-section avatar>
                  <q-icon :name="item.icon"/>
                </q-item-section>
                <q-item-section>{{ item.label }}</q-item-section>
              </q-item>

              <q-item class="q-ma-sm navigation-item" active-class="tab-active" to="/" exact clickable v-ripple
                      @click="logout">
                <q-item-section avatar>
                  <q-icon name="logout"/>
                </q-item-section>
                <q-item-section>Deconnexion</q-item-section>
              </q-item>
            </q-list>
          </q-scroll-area>
        </div>
      </div>
    </q-drawer>

    <q-page-container>
      <q-page class="row no-wrap">
        <div class="col">
          <div class="full-height">
            <q-scroll-area class="col q-pr-sm full-height" visible>
              <router-view/>
            </q-scroll-area>
          </div>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
  data() {
    return {
      left: false,
    };
  },
  computed: {
    ...mapGetters('layout', ['getLayoutConfig']),
    ...mapGetters('login', ['isLoggedIn', 'getEmail'])
  },
  methods: {
    logout() {
      this.$store.dispatch('login/Logout')
      this.$q.notify({
        message: this.$t('Logout'),
        timeout: 2500
      });
    }
  },
  created() {
    this.$store.dispatch('layout/FetchLayout')
  }
};
</script>
