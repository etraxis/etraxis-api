<template>
    <div>
        <div class="flex-row toolbar">
            <button type="button" @click="goBack">{{ i18n['button.back'] }}</button>
        </div>
        <div class="flex-row">
            <div class="flex-column width-50 mobile-width-100">
                <fieldset class="width-100">
                    <div class="form-row">
                        <p class="width-33 mobile-width-100">{{ i18n['user.fullname'] }}:</p>
                        <p class="width-66 mobile-width-100">{{ profile.fullname }}</p>
                    </div>
                    <div class="form-row">
                        <p class="width-33 mobile-width-100">{{ i18n['user.email'] }}:</p>
                        <p class="width-66 mobile-width-100">{{ profile.email }}</p>
                    </div>
                    <div class="form-row">
                        <p class="width-33 mobile-width-100">{{ i18n['user.permissions'] }}:</p>
                        <p class="width-66 mobile-width-100">{{ i18n[profile.admin ? 'role.admin' : 'role.user'] }}</p>
                    </div>
                    <div class="form-row">
                        <p class="width-33 mobile-width-100">{{ i18n['user.authentication'] }}:</p>
                        <p class="width-66 mobile-width-100">{{ providers[profile.provider] }}</p>
                    </div>
                    <div class="form-row">
                        <p class="width-33 mobile-width-100">{{ i18n['user.description'] }}:</p>
                        <p class="width-66 mobile-width-100">{{ profile.description || '&mdash;' }}</p>
                    </div>
                    <div class="form-row">
                        <p class="width-33 mobile-width-100">{{ i18n['user.status'] }}:</p>
                        <p v-if="profile.locked" class="width-66 mobile-width-100">{{ i18n['user.locked'] }}</p>
                        <p v-else class="width-66 mobile-width-100">{{ i18n[profile.disabled ? 'user.disabled' : 'user.enabled'] }}</p>
                    </div>
                    <div class="form-row">
                        <p class="width-33 mobile-width-100">{{ i18n['user.language'] }}:</p>
                        <p class="width-66 mobile-width-100">{{ locales[profile.locale] }}</p>
                    </div>
                    <div class="form-row">
                        <p class="width-33 mobile-width-100">{{ i18n['user.timezone'] }}:</p>
                        <p class="width-66 mobile-width-100">{{ profile.timezone }}</p>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
</template>

<script src="./tab-profile.js"></script>
