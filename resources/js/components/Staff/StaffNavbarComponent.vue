<template>
    <div>

        <b-navbar>
            <template #brand>
                <b-navbar-item>
                    <img
                        src=""
                        alt=""
                    >
                </b-navbar-item>
            </template>
            <template #start>
                
            </template>

            <template #end>
             
                <b-navbar-item href="/staff-home">
                    Home
                </b-navbar-item>

                <b-navbar-item href="#">
                    {{ firstName }}
                </b-navbar-item>

                <b-navbar-item tag="div">
                    <div class="buttons">
                        <b-button 
                            @click="logout"
                            icon-left="logout"
                            class="button is-primary" outlined>
                        </b-button>
                    </div>
                </b-navbar-item>
            </template>
        </b-navbar>


    </div>
</template>

<script>

export default{
    data(){
        return {

            open: true,
            expandWithDelay: false,

            user: {
                fname: '',
            },
        }
    },

    methods: {
        logout(){
            axios.post('/logout').then(res=>{
                window.location = '/'
            }).catch(err=>{
            
            })
        },

        loadUser(){
            axios.get('/get-user').then(res=>{
                this.user = res.data;
            })
        }
    },

    mounted(){
        this.loadUser()
    },

    computed: {
        firstName(){
            return this.user.fname.toUpperCase()
        }
    }
}
</script>
