<template>
    <li>
        <a :href="url ? url : '#'" :title="title" @click="onClick">
            <span :class="[ 'hide-on-mobile', 'fa', icon ]" aria-hidden="true"></span>
            <span class="hide-on-tablet">
                <slot></slot>
            </span>
        </a>
    </li>
</template>

<script src="./menuitem.js"></script>
