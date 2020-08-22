<template>
    <th :class="{ sortable }" :width="width" @click="onClick">
        <div>
            <span class="title"><slot></slot></span>
            <span v-if="sortable" class="fa" :class="{ 'fa-caret-up': $parent.proxySorting[id] === $parent.sortAsc, 'fa-caret-down': $parent.proxySorting[id] === $parent.sortDesc }"></span>
        </div>
    </th>
</template>

<script src="./column.js"></script>
