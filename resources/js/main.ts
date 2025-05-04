import {createApp} from 'vue';
import App from './App.vue';
import {useUserStore} from './stores/userStore';

const app = createApp(App);

// Initialize global store
app.runWithContext(async () => {
    const userStore = useUserStore();
    await userStore.initialize();
}).then();

app.mount('#app');
