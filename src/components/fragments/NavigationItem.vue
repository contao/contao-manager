<template>
    <router-link :to="to" custom v-slot="{ isActive, href, navigate }">
        <li :class="{ [`navigation__item navigation__item--${sub ? 'sub' : 'main'}`]: true, 'router-link-active': isActive}">
            <a :href="href" @click="go(navigate)" @keypress.enter="go(navigate)">
                <slot/>
            </a>
        </li>
    </router-link>
</template>

<script>
export default {
    props: {
        to: Object,
        sub: Boolean,
    },

    methods: {
        go(next) {
            this.$emit('navigate');
            next();
        },
    }
}
</script>
