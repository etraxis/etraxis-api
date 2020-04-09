<template>
    <button type="button" class="dropdown" @click.stop="onButtonClick">
        <slot></slot>
        <span class="fa fa-caret-down"></span>
        <ul v-show="!isHidden">
            <li v-for="item in items" :aria-disabled="item.disabled" @click.stop="onItemClick(item)">{{ item.text }}</li>
        </ul>
    </button>
</template>

<script src="./dropdown.js"></script>
