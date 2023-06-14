Vue.component('auto-complete', {
    template:`
    <div>
      <input type="text" placeholder="Enter Country name..." v-model="query" @keyup="getData()" autocomplete="off" class="form-control input-lg" />
      <div class="panel-footer" v-if="search_data.length">
        <ul class="list-group">
          <a href="#" class="list-group-item" v-for="data1 in search_data" @click="getName(data1.country_name)">{{ data1.country_name }}</a>
        </ul>
      </div>
    </div>
    `,
    data:function(){
      return{
        query:'',
        search_data:[]
      }
    },
    methods:{
      getData:function(){
        this.search_data = [];
        axios.post('fetch.php', {
          query:this.query
        }).then(response => {
          this.search_data = response.data;
        });
      },
      getName:function(name){
        this.query = name;
        this.search_data = [];
      }
    }
  });

  /*var application = new Vue({
    el:'#autocomplete_app'
  });*/
