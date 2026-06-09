<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="flex justify-center">
          <i class="pi pi-book text-6xl text-blue-600"></i>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Регистрация
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          Или
          <router-link
            to="/login"
            class="font-medium text-blue-600 hover:text-blue-500"
          >
            войдите в систему
          </router-link>
        </p>
      </div>

      <Card class="!shadow-md">
        <template #content>
          <form @submit.prevent="onSubmit" class="space-y-6">
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email
              </label>
              <InputText
                id="email"
                v-model="email"
                type="email"
                placeholder="your@email.com"
                :class="{'p-invalid': errors.email}"
                class="w-full"
                autofocus
              />
              <small v-if="errors.email" class="text-red-600">{{ errors.email }}</small>
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Пароль
              </label>
              <Password
                id="password"
                v-model="password"
                placeholder="••••••••"
                :class="{'p-invalid': errors.password}"
                toggleMask
                class="w-full"
                inputClass="w-full"
              />
              <small v-if="errors.password" class="text-red-600">{{ errors.password }}</small>
            </div>

            <div>
              <label for="confirmPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Подтверждение пароля
              </label>
              <Password
                id="confirmPassword"
                v-model="confirmPassword"
                placeholder="••••••••"
                :feedback="false"
                :class="{'p-invalid': errors.confirmPassword}"
                toggleMask
                class="w-full"
                inputClass="w-full"
              />
              <small v-if="errors.confirmPassword" class="text-red-600">{{ errors.confirmPassword }}</small>
            </div>

            <Message v-if="authStore.error" severity="error" :closable="false">
              {{ authStore.error }}
            </Message>

            <Button
              type="submit"
              label="Зарегистрироваться"
              :loading="authStore.isLoading"
              class="w-full"
              size="large"
            />
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { z } from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const validationSchema = toTypedSchema(
  z.object({
    email: z.string().email('Некорректный email').min(1, 'Email обязателен'),
    password: z.string().min(6, 'Пароль должен содержать минимум 6 символов'),
    confirmPassword: z.string().min(6, 'Подтверждение пароля обязательно'),
  }).refine((data) => data.password === data.confirmPassword, {
    message: 'Пароли не совпадают',
    path: ['confirmPassword'],
  })
)

const { errors, handleSubmit, defineField } = useForm({
  validationSchema,
})

const [email] = defineField('email')
const [password] = defineField('password')
const [confirmPassword] = defineField('confirmPassword')

const onSubmit = handleSubmit(async (values) => {
  const success = await authStore.register({
    email: values.email,
    password: values.password,
  })

  if (success) {
    router.push({ name: 'dashboard' })
  }
})
</script>
