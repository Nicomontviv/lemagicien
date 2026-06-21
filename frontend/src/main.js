import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

const app = createApp(App)

// Le decimos a la app que use el sistema de rutas
app.use(router)

app.mount('#app')