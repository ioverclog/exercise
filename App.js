import React, { Component } from 'react';
import SearchInput, {createFilter} from './lib/react-search-input'
import {BrowserView, MobileView, isBrowser, isMobile} from 'react-device-detect'
import {KEYS_TO_FILTERS, KEYS_TO_FILTERS_REGION} from './data_set';
import './App.css';
import DataRequest from './DataRequest';

Array.prototype.moveNoticeElement = function(){
    let to = 0;
    this.forEach((element, index)=>{
        if(element.notice == 'true'){
            this.splice(to,0,this.splice(index,1)[0]);
            to++;
        }
    });
    return this;
};

const Util = {
    sortSubRegion : (a, b)=>{
        let prev = a.subRegion;
        let next = b.subRegion;
        if (prev > next) return 1;
        if (next > prev) return -1;
        return 0;
    },
    sortRegion : (a, b)=>{
        let prev = a.region;
        let next = b.region;

        if (prev > next) return 1;
        if (next > prev) return -1;
        return 0;
    },
    isObject : (o)=>{
        return o instanceof Object && o.constructor === Object;
    },
    assignObj : (data, callBack)=>{
        return Object.assign({}, ...Object.keys(data).map(callBack));
    },
    setClassSearchWords : (data, searchTerm)=>{
        let termArr = searchTerm.split(' ').filter((term)=>{
            return term;
        });
        termArr.forEach((term)=>{
            let spanTerm = '<span class="searchBg">' + term + '</span>';
            let searchRegEx = new RegExp(term, "g");
            data = data.replace(searchRegEx, spanTerm);
        });
        return data
    },
    getSearchTermResult : (value, searchTerm, key=undefined)=>{
        let result;
        if(key){
            result = {[key]: Util.setClassSearchWords(value, searchTerm)};
        } else {
            result = Util.setClassSearchWords(value, searchTerm);
        }
        return result;
    },
    getSearchBgObject : (datas, searchTerm)=>{
        return Util.assignObj(datas, (key)=>{
            let value = datas[key];
            if(Array.isArray(value)){
                let result = value.map((data)=>{
                    return Util.assignObj(data, (key)=>{
                        let value = data[key];
                        if(Array.isArray(value)){
                            let result = value.map((value)=>{
                                return Util.getSearchTermResult(value, searchTerm);
                            });
                            return {[key]: result}
                        } else {
                            return Util.getSearchTermResult(value, searchTerm, key)
                        }
                    });
                });
                return {[key]: result};
            }else{
                return Util.getSearchTermResult(value, searchTerm, key);
            }
        });
    },
    setSearchBg : (datas, searchTerm)=>{
        if(!searchTerm) return;
        if(Array.isArray(datas)){
            return datas.map((data)=>{
                return Util.getSearchBgObject(data, searchTerm);
            });
        }else if(Util.isObject(datas)){
            return Util.getSearchBgObject(datas, searchTerm);
        }else{
            return Util.getSearchTermResult(datas, searchTerm)
        }
    }
};

const SubRegion = ({data, idx, lastRegion, lastSubResion}) => {
    const info = data.info.map((obj, index)=>{
        let tel = obj.tel.map((num, idx)=>{
            let comma = '';
            if (obj.tel.length != idx + 1) {
                comma = '   <br />';
            }
            return num + comma;
        }).join('');

        let nextInfo = '';
        if (data.info.length != index + 1) {
            nextInfo = '<br />';
        }

        return obj.name + ' ' + tel + nextInfo;
    }).join('');

    return (
        <div className={`contContainer ${idx > 0 ? 'topline' : ''} ${(lastRegion && lastSubResion) ? 'bottomline' : ''}`}>
            <div className="c1 inner" dangerouslySetInnerHTML={{__html: data.subRegion}}>{}</div>
            <div className="c2 inner" dangerouslySetInnerHTML={{__html: data.detailRegion}}></div>
            <div className="c3 inner" dangerouslySetInnerHTML={{__html: info}}></div>
        </div>
    )
};
class Region extends Component {
    constructor(props) {
        super(props);
    }

    render() {
        let {data, lastRegion, searchTerm} = this.props;

        let region = data.region;
        let datas = data.subRegions
            .sort(Util.sortSubRegion)
            .filter(createFilter(searchTerm, KEYS_TO_FILTERS_REGION));

        if( datas.length == 0 ){
            datas = data.subRegions
                .sort(Util.sortSubRegion)
        }

        if(!!searchTerm){
            datas = Util.setSearchBg(datas, searchTerm);
            region = Util.setSearchBg(region, searchTerm);
        }

        return (
            <div>
                <div className="region topline">
                    <div className="regionName" dangerouslySetInnerHTML={{__html: region}}></div>
                </div>
                {datas.map((subRegion, index) => {
                        let lastSubResion = false;
                        if (index == data.subRegions.length - 1) {
                            lastSubResion = true;
                        }
                        let options = {
                            data: subRegion,
                            key : index,
                            idx : index,
                            lastRegion: lastRegion,
                            lastSubResion : lastSubResion
                        };
                        return <SubRegion {...options} />
                    }
                )}
            </div>
        )
    }
}
class SearchResult extends Component {
    constructor(props) {
        super(props);
    }

    render() {
        let {searchTerm, datas, koreaRegionData} = this.props;
        const $searchResult = (
            koreaRegionData.map((region, idx)=>{
                let TopDataCont = ({data}) => {
                    data = Util.setSearchBg(data, searchTerm);

                    let title = data.title;
                    let info = data.info.map((data)=>{
                        return data.name + ' ' + data.tel;
                    }).join(',');
                    return (
                        <li key={idx} dangerouslySetInnerHTML={{__html: title + ' : ' + info}}></li>
                    )
                };

                let topData = datas[region].topData
                    .filter(createFilter(this.props.searchTerm, ['region.title','region.info.name','region.info.tel']))
                    .map((obj, index)=>
                        <div key={index}>
                            <h3>{obj.title}</h3>
                            <ul>
                                {obj.region
                                    .filter(createFilter(searchTerm, ['title','info.name','info.tel']))
                                    .map((data, idx)=><TopDataCont data={data} key={idx} />)
                                }
                            </ul>
                        </div>);

                let regionData = datas[region].regionData
                    .sort(Util.sortRegion)
                    .moveNoticeElement()
                    .filter(createFilter(searchTerm, KEYS_TO_FILTERS));

                return (
                    (topData.length > 0 || regionData.length > 0) &&
                    <div key={idx} className="regionContBody">
                        {<h2>{region} 지역 연공장 안내</h2>}
                        {topData.length > 0 && <div className="topCont">{topData}</div>}
                        {regionData.length > 0 && regionData.map((data, idx)=>{
                            let options = {
                                data: data,
                                key: idx,
                                lastRegion : (idx ==regionData.length-1)? true : false,
                                searchTerm: searchTerm
                            };
                            return <Region {...options} />
                        })}
                    </div>
                )
            })
        );

        let isSearched = $searchResult.some((item)=>item);

        return (
            <div>
                {isSearched && $searchResult}
                {!isSearched && <div className="searchResult"><strong>[{searchTerm}]</strong> 와 일치하는 정보가 없습니다.</div>}
            </div>
        )
    }


}
class App extends Component {
    constructor(props) {
        super(props);

        this.state = {
            searchTerm: '',
            koreaRegionData: '',
            region : '',
            datas : ''
        };

        //this.searchUpdated = this.searchUpdated.bind(this);
    }

    componentDidMount() {
        this.searchInput = document.querySelectorAll('.search-input input')[0];
        this.searchClearBtn = document.getElementById('searchClearBtn');
        this.searchLens = document.getElementById('searchLens');
        
        this.getExerciseRegionData();
    }
    
    async getExerciseRegionData( id ) {
        let params = {
            "id" : id
        }
            
        try {
            const response = await DataRequest.getRegionData(params);
            const koreaRegionData = response['koreaRegionData'];

            this.setState({
                koreaRegionData: koreaRegionData,
                region: koreaRegionData[0],
                datas: response
            });
        } catch (e) {
        }
    }
    
    async insertExerciseRegionData() {
        let params = {
            method: "POST",
            data: {
                _method: "post",
                notice : 0,
                address1 : 'seoul',
                address2 : 'kangnam',
                address3 : 'sinsa',
                detailAddress: 'garosu-gil apple',
                startTime : '10:00 ~ 12:00',
                userName : 'steve jobs',
                tel : '010-sss-ssss'
            }
        }
        try {
            const response = await DataRequest.insertRegionData(params);
        } catch (e) {
        }
    }
    
    clickInsert() {
        this.getExerciseRegionData();
    }
    
    clickRegion(e, region) {
        e.preventDefault();
        let regionName = (typeof region === 'string')? region : e.target.value;
        this.setState({
            searchTerm: '',
            region: regionName,
            subRegion: ''
        });
    }
    searchUpdated (term) {
        if(term == undefined){
            term = this.searchInput.value; //for IE9
        }
        this.setState({searchTerm: term});
        this.toggleSearchClearBtn();
    }
    setInputBlur(e) {
        e.preventDefault();
        this.setState({searchTerm: ''});
        this.toggleSearchClearBtn();
    }
    toggleSearchClearBtn() {
        if( this.state.searchTerm ){
            this.searchClearBtn.style.display = 'block';
            this.searchLens.style.display = 'none';
        } else {
            this.searchClearBtn.style.display = 'none';
            this.searchLens.style.display = 'block';
        }
    }
    clickSubRegion(e, subRegion) {
        e.preventDefault();
        if( subRegion == undefined ) subRegion = e.target.value;
        if( subRegion == '전체' ) subRegion = '';
        this.setState({
            subRegion: subRegion
        });
    }
    onFocus() {
        if( isMobile ){
            this.searchInput.scrollIntoView(true);
        }
    }

    render() {
        const datas = this.state.datas;
        const region = this.state.region;

        const topdata = !!datas && !!region && datas[region].topData;
        const $topCont = (
            <div className="topCont">
                {!!topdata && topdata.length > 0 && topdata.map((obj, index)=>
                    <div className={`${topdata.length > 1 && index == (topdata.length - 1) ? 'info' : ''}`} key={index}>
                        <h3>{obj.title}</h3>
                        <ul>
                            {obj.region.map((data, idx) =>
                                <li key={idx}>{data.title} : {data.info.map((data)=> {
                                    return data.name + ' ' + data.tel;
                                }).join(',')}</li>
                            )}
                        </ul>
                    </div>
                )}
            </div>
        );

        const regionData = !!datas && !!region && datas[region].regionData;
        const $bodyCont = (
            <div className="rightCont-bottom">
                {!!regionData && regionData
                    .sort(Util.sortRegion)
                    .moveNoticeElement()
                    .filter(createFilter(this.state.searchTerm, KEYS_TO_FILTERS))
                    .filter((data)=>{
                        let result = true;
                        if( this.state.subRegion ){
                            result = (this.state.subRegion == data.region )? true : false;
                        }
                        return result;
                    })
                    .map((data, idx)=> {
                            let options = {
                                data : data,
                                key : idx,
                                lastRegion : ( idx == regionData.length - 1 )? true : false,
                                searchTerm : this.state.searchTerm
                            };
                            return <Region {...options} />
                        }
                    )}
            </div>
        );

        return (!!datas && !!region &&
            <div className="table">
                <div className="searchCont">
                    <div id="searchClearBtn" onClick={this.setInputBlur.bind(this)}><span><a href="#">X</a></span></div>
                    <SearchInput className="search-input ty_sch" placeholder="이름, 전화번호, 지역명" value={this.state.searchTerm} onFocus={this.onFocus.bind(this)} onChange={this.searchUpdated.bind(this)} />
                    <div id="searchLens">&nbsp;</div>
                </div>
                {!!this.state.searchTerm && <SearchResult searchTerm={this.state.searchTerm} datas={this.state.datas} koreaRegionData={this.state.koreaRegionData} />}
                {!this.state.searchTerm &&
                <div className="searchRegionCont">
                    <div className="searchBox">
                        <div className="mobile">
                            <div className="search-region-select-level1">
                                <select onChange={(e)=>this.clickRegion(e)} value={this.state.region}>
                                    {this.state.koreaRegionData.map((region, idx)=>{
                                        return <option key={idx} value={region}>{region}</option>
                                    })}
                                </select>
                            </div>
                            <div className="search-region-select-level2">
                                <select onChange={(e)=>this.clickSubRegion(e)} value={this.state.subRegion}>
                                    <option key={0} value="전체">전체</option>
                                    {regionData && regionData.map((items, idx)=>{
                                        return <option key={idx} value={items.region}>{items.region}</option>
                                    })}
                                </select>
                            </div>
                        </div>
                        <div className="pc">
                            <div className="search-region-level1">
                                <ul>
                                    {this.state.koreaRegionData.map((region, idx)=>{
                                        if( this.state.region == region ){
                                            return <li key={idx} className="axis span-width-50"><span className="radiWrap">{region} <em>({datas[region].regionData.length})</em></span></li>
                                        } else {
                                            return <li key={idx} className="span-width-50" onClick={(e)=>this.clickRegion(e, region)}><span><a href="#">{region}</a> <em>({datas[region].regionData.length})</em></span></li>
                                        }
                                    })}
                                </ul>
                            </div>
                            <div className="search-region-level2">
                                <ul>
                                    {!this.state.subRegion && <li key={0} className="axis span-width-30"><span className="radiWrap">전체 <em>({datas[this.state.region].regionData.length})</em></span></li>}
                                    {this.state.subRegion && <li key={0} className="span-width-30" onClick={(e)=>this.clickSubRegion(e, '')}><span><a href="#">전체</a> <em>({datas[this.state.region].regionData.length})</em></span></li>}

                                    {regionData && regionData.map((items, idx)=>{
                                        if( this.state.subRegion == items.region ){
                                            return <li key={idx + 1} className={`axis ${(items.region.length > 8)? 'span-width-50' : 'span-width-30'}`}><span className="radiWrap">{items.region} <em>({regionData[idx].subRegions.length})</em></span></li>
                                        } else {
                                            return <li key={idx + 1} className={`${(items.region.length > 8)? 'span-width-50' : 'span-width-30'}`} onClick={(e)=>this.clickSubRegion(e, items.region)}><span><a href="#">{items.region}</a> <em>({regionData[idx].subRegions.length})</em></span></li>
                                        }
                                    })}
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div className="regionContBody">
                        <h2>{this.state.region} 지역 연공장 안내</h2>
                        <button onClick={(e)=>this.clickInsert(e)}>POST insert</button>
                        {!this.state.subRegion && $topCont}
                        {$bodyCont}
                    </div>
                </div>
                }
            </div>
        );
    }
}

export default App;
