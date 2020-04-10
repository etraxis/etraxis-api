<template>
    <div class="tabs">
        <ul>
            <li v-for="tab in tabs" :id="tab.id" :class="{ 'active': tab.active }">
                <a @click="activateTab(tab.id)">{{ tab.caption }}</a>
            </li>
        </ul>
        <slot></slot>
    </div>
</template>

<script src="./tabs.js"></script>
