<template>
    <div>
        <div class="flex-row toolbar">
            <button @click="changePassword">{{ i18n['password.change'] }}</button>
        </div>
        <div class="flex-row">
            <div class="flex-column width-50 mobile-width-100">
                <fieldset class="width-100">
                    <legend>{{ i18n['user.password'] }}</legend>
                    <div class="form-row">
                        <label class="width-33 mobile-width-100" for="password-current">{{ i18n['password.current'] }}:</label>
                        <input class="width-66 mobile-width-100" type="password" id="password-current" autocomplete="new-password" :placeholder="i18n['input.required']" v-model="currentPassword">
                    </div>
                    <div class="form-row">
                        <label class="width-33 mobile-width-100" for="password-new">{{ i18n['password.new'] }}:</label>
                        <input class="width-66 mobile-width-100" type="password" id="password-new" autocomplete="new-password" :placeholder="i18n['input.required']" v-model="newPassword">
                        <p class="danger offset-33 mobile-offset-0">{{ errors.password }}</p>
                    </div>
                    <div class="form-row">
                        <label class="width-33 mobile-width-100" for="password-confirm">{{ i18n['password.confirm'] }}:</label>
                        <input class="width-66 mobile-width-100" type="password" id="password-confirm" autocomplete="new-password" :placeholder="i18n['input.required']" v-model="passwordConfirmation">
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
</template>

<script src="./tab-password.js"></script>
