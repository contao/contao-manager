<template>
    <router-link :to="to" custom v-slot="{ isActive, href, navigate }">
        <li :class="{ [`navigation__item navigation__item--${sub ? 'sub' : 'main'}`]: true, 'router-link-active': isActive}">
            <a :href="href" @click="navigate" @keypress.enter="navigate">
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
    }
}
</script>
