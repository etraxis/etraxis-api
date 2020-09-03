<template>
    <div>
        <div class="flex-row toolbar">
            <button @click="saveChanges">{{ i18n['button.save'] }}</button>
        </div>
        <div class="flex-row">
            <div class="flex-column width-50 mobile-width-100">
                <fieldset class="width-100">
                    <legend>{{ i18n['user'] }}</legend>
                    <div class="form-row">
                        <label class="width-33 mobile-width-100" for="fullname">{{ i18n['user.fullname'] }}:</label>
                        <input class="width-66 mobile-width-100" type="text" id="fullname" :placeholder="i18n['input.required']" :readonly="isReadOnly" v-model.trim="values.fullname">
                        <p class="danger offset-33 mobile-offset-0">{{ errors.fullname }}</p>
                    </div>
                    <div class="form-row">
                        <label class="width-33 mobile-width-100" for="email">{{ i18n['user.email'] }}:</label>
                        <input class="width-66 mobile-width-100" type="text" id="email" :placeholder="i18n['input.required']" :readonly="isReadOnly" v-model.trim="values.email">
                        <p class="danger offset-33 mobile-offset-0">{{ errors.email }}</p>
                    </div>
                </fieldset>
                <fieldset class="width-100">
                    <legend>{{ i18n['user.appearance'] }}</legend>
                    <div class="form-row">
                        <label class="width-33 mobile-width-100" for="language">{{ i18n['user.language'] }}:</label>
                        <select class="width-66 mobile-width-100" id="language" v-model="values.locale">
                            <option v-for="(value, name) in locales" :value="name">{{ value }}</option>
                        </select>
                        <p class="danger offset-33 mobile-offset-0">{{ errors.locale }}</p>
                    </div>
                    <div class="form-row">
                        <label class="width-33 mobile-width-100" for="theme">{{ i18n['user.theme'] }}:</label>
                        <select class="width-66 mobile-width-100" id="theme" v-model="values.theme">
                            <option v-for="(value, name) in themes" :value="name">{{ value }}</option>
                        </select>
                        <p class="danger offset-33 mobile-offset-0">{{ errors.theme }}</p>
                    </div>
                </fieldset>
            </div>
            <div class="flex-column width-50 mobile-width-100">
                <fieldset class="width-100">
                    <legend>{{ i18n['user.timezone'] }}</legend>
                    <div class="form-row">
                        <label class="width-33 mobile-width-100" for="country">{{ i18n['user.country'] }}:</label>
                        <select class="width-66 mobile-width-100" id="country" v-model="country">
                            <option value="UTC">UTC</option>
                            <option v-for="(value, name) in countries" :value="name">{{ value }}</option>
                        </select>
                        <p v-show="country === 'UTC'" class="danger offset-33 mobile-offset-0">{{ errors.timezone }}</p>
                    </div>
                    <div v-show="country !== 'UTC'" class="form-row">
                        <label class="width-33 mobile-width-100" for="city">{{ i18n['user.city'] }}:</label>
                        <select class="width-66 mobile-width-100" id="city" v-model="values.timezone">
                            <option v-for="(value, name) in cities" :value="name">{{ value }}</option>
                        </select>
                        <p class="danger offset-33 mobile-offset-0">{{ errors.timezone }}</p>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
</template>

<script src="./tab-profile.js"></script>
