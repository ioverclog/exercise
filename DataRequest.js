import axios from 'axios';

const http = axios.create({});

http.interceptors.request.use(
    function(config){
      /*
      config.header["Cache-Control"] = "no-cache";
      config.header["Pragma"] = "no-cache";
      config.header["Content-Type"]="application/json;charset=UTF-8";
      */
      return config;
    }
    function(error){
      return Promise.reject(error);
    }
);
http.interceptors.response.use(
    function(response){
        function decodeHtmlEntity(str) {
            str = (typeof str !== 'string') ? JSON.stringify(str) : str;
            return str.replace(/&#(\d{2,});*/g, function(match, dec) {
                return String.fromCharCode(dec);
            });
        }
        response=JSON.parse(decodeHtmlEntity(response));
        return response.data;
    },
    function(error){
        return Promise.reject(error);
    }
);

function APIService(config, opt){
    if(!config.params){
        config.params={}
    }
    
    let cancelFunc = ()=> {};
    const apiOptions = {
        cancelToken: new axios.CancelToken(c=>{
            cancelFunc = c;
        }),
        ...opt      
    };
    
    let url = config.url;
    
    if( config.method === "GET" ){
        const getPromise = http.get(url, {
            params: config.params,
            ...apiOptions
        });
        getPromise.cancel = cancelFunc;
        return getPromise;
    } else if( config.method === "POST" ){
        let postParams = Object.assign(apiOptions, config.params);
        const postPromise = http.post(url, postParams);
        postPromise.cancel = cancelFunc;
        return postPromise;
    }       
}

const RegionDAO = {
    serverUrl : "http://localhost:63342/exerciseApi/api/apiTest.php",
    getRegionData: function (params) {
        let config = {
            type: "API",
            method: "GET",
            url: this.serverUrl,
            params: params
        };
        return APIService(config);
    },
    insertRegionData: function (params) {
        let config = {
            type: "API",
            method: "POST",
            url: this.serverUrl,
            params: params
        };
        return APIService(config);
    },
    deleteRegionData: function (params) {
        let config = {
            type: "API",
            method: "DELETE",
            url: this.serverUrl,
            params: params
        };
        return APIService(config);
    },
    editRegionData: function (params) {
        let config = {
            type: "API",
            method: "DELETE",
            url: this.serverUrl,
            params: params
        };
        return APIService(config);
    },
};
const DataResource = (()=>{
    let region_data = null;
    return {
        regionData: function (data) {
            !!data && (region_data = data);
            return region_data;
        }
    }
})();
export default (() => {
    return {
        getRegionData: async function (params) {
            const response = await RegionDAO.getRegionData(params);
            return DataResource.regionData(response);
        },
        insertRegionData: async function (params) {
            const response = await RegionDAO.insertRegionData(params);
            return response;
        },
        deleteRegionData: async function (params) {
            const response = await RegionDAO.deleteRegionData(params);
            return response;
        },
        editRegionData: async function (params) {
            const response = await RegionDAO.editRegionData(params);
            return response;
        }
    }
})();
    
 










