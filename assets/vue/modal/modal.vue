<template>
    <dialog @cancel="$emit('cancel')">
        <header>
            <p>{{ header }}</p>
            <span class="fa fa-remove" :title="i18n['button.close']" @click="cancel"></span>
        </header>
        <main>
            <slot></slot>
        </main>
        <footer>
            <slot name="footer">
                <button type="button" @click="cancel">{{ i18n['button.close'] }}</button>
            </slot>
        </footer>
    </dialog>
</template>

<script src="./modal.js"></script>
