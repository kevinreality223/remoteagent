import { createRouter, createWebHashHistory } from 'vue-router';
import MasterView from '../views/MasterView.vue';
import ClientView from '../views/ClientView.vue';

const routes = [
  { path: '/', redirect: '/master' },
  { path: '/master', name: 'master', component: MasterView },
  { path: '/clients/:id', name: 'client', component: ClientView, props: true }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes
});

export default router;
