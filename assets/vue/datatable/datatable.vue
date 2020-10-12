<template>
    <div class="datatable">
        <div class="header" :class="{ simplified }">
            <select v-if="paging" class="size" :disabled="blocked" v-model="proxyPageSize">
                <option v-for="pageSize in allowedPageSizes" :key="pageSize" :value="pageSize">{{ i18n['table.size'].replace('%size%', pageSize) }}</option>
            </select>
            <div v-if="paging" class="paging buttonset">
                <button type="button" class="fa first-page" :disabled="blocked || pages === 0 || proxyPage === 1" :title="i18n['page.first']" @click="proxyPage = 1"></button>
                <button type="button" class="fa previous-page" :disabled="blocked || pages === 0 || proxyPage === 1" :title="i18n['page.previous']" @click="proxyPage -= 1"></button>
                <input class="page" type="text" :readonly="blocked" :disabled="pages === 0" :title="i18n['table.pages'].replace('%number%', pages)" v-model.trim.lazy.number="userPage">
                <button type="button" class="fa next-page" :disabled="blocked || pages === 0 || proxyPage === pages" :title="i18n['page.next']" @click="proxyPage += 1"></button>
                <button type="button" class="fa last-page" :disabled="blocked || pages === 0 || proxyPage === pages" :title="i18n['page.last']" @click="proxyPage = pages"></button>
            </div>
            <p class="status">{{ status }}</p>
            <div class="search">
                <div class="buttonset">
                    <button type="button" class="fa fa-refresh" :title="i18n['button.refresh']" :disabled="blocked" @click="refresh"></button>
                    <button type="button" class="fa fa-times" :title="i18n['button.reset_filters']" :disabled="blocked" @click="resetFilters"></button>
                </div>
                <input type="text" :placeholder="i18n['button.search']" :readonly="blocked" v-model.trim="proxySearch">
            </div>
        </div>
        <table :class="{ 'hover': clickable && !blocked, checkboxes }">
            <thead>
            <tr>
                <th v-if="checkboxes" @click="totalFilters === 0 ? checkedAll = !checkedAll : null">
                    <input v-if="totalFilters === 0 && total !== 0" type="checkbox" :indeterminate.prop="!checkedAll && proxyChecked.length !== 0" :disabled="blocked" @click.stop v-model="checkedAll">
                </th>
                <slot></slot>
            </tr>
            </thead>
            <tfoot v-if="totalFilters !== 0">
            <tr>
                <td v-if="checkboxes" @click="checkedAll = !checkedAll">
                    <input v-if="total !== 0" type="checkbox" :indeterminate.prop="!checkedAll && proxyChecked.length !== 0" :disabled="blocked" @click.stop v-model="checkedAll">
                </td>
                <td v-for="column in columns" :key="column.id">
                    <input v-if="column.filterable && !column.isDropdownFilter" type="text" :readonly="blocked" v-model.trim="column.proxyFilterValue">
                    <select v-if="column.filterable && column.isDropdownFilter" :disabled="blocked" v-model.trim="column.proxyFilterValue">
                        <option></option>
                        <option v-for="(value, key) in column.filterWith" :key="key" :value="key">{{ value }}</option>
                    </select>
                </td>
            </tr>
            </tfoot>
            <tbody>
            <tr v-if="total === 0" class="empty">
                <td :colspan="checkboxes ? columns.length + 1 : columns.length">{{ i18n['table.empty'] }}</td>
            </tr>
            <tr v-for="row in rows" :key="row.DT_id" :class="row.DT_class">
                <td v-if="checkboxes" @click="row.DT_checkable !== false ? toggleCheck(row.DT_id) : null">
                    <input type="checkbox" :disabled="blocked || row.DT_checkable === false" :value="row.DT_id" @click.stop v-model="proxyChecked">
                </td>
                <td v-for="column in columns" :key="column.id" :class="{ 'wrappable': column.width }" @click="clickable ? $emit('click', row.DT_id, column.id) : null">
                    <span>{{ row[column.id] ? row[column.id] : '&mdash;' }}</span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script src="./datatable.js"></script>
