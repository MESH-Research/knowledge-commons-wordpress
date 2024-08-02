import React from 'react';
import Sidebar from "./Sidebar";
import {q_popularGenre, q_popularTags} from "../graphql/bin/queries";
import {ScaleLoader} from "react-spinners";
import {SearchForm} from "./SearchForm";

//this is basically a search dashboard.
class SearchPage extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            tagData: null,
            typeData: null,
        };
        this.getData();
        document.title = "Digital Pedagogy | Search";
    }

    //dashboard data objects.
    getData() {
        q_popularTags().then(results => {
            return this.setState({tagData: results.data.tags.edges.map((obj) => obj.node.name)});
        });

        q_popularGenre().then(results => {
            return this.setState({typeData: results.data.dp_genres.edges.map((obj) => obj.node.name)});
        });
    }

    // data formatter
    tagData() {
        const tagData = this.state.tagData;
        if (!tagData) {
            return false;
        }
        return {
            title: 'Search by Tag',
            items: tagData.map((tag) => ({name: tag, url: "/search/tag/" + tag.replace(" ", "+").replace("-",'\u2013')}))
        };
    }

    // data formatter
    typeData() {
        const typeData = this.state.typeData;
        if (!typeData) {
            return false;
        }
        return {
            title: 'Search by Artifact Type',
            items: typeData.map((type) => (type !== "Curation statement" && {name: type, url: "/search/type/"+ type.replace(" ", "+").replace("-",'\u2013')}))
        };
    }
    componentDidMount() {
        window.scrollTo(0, 0)
    }
    searchTitle_HTML() {
        return (
            <div className="row search_body_title">
                <label htmlFor="search"><h1 className={"col-sm search_body_title_value"}>Search artifacts and keywords</h1></label>
            </div>
        );
    }

    searchByTag_HTML() {
        const tagData = this.tagData();
        if (!tagData) {
            return false;
        }
        return (<div className='row search__body__section'>
                <Sidebar bemRoot={"search__body__section__by-tags"}
                         list={tagData}/>
            </div>
        );
    }

    searchByType_HTML() {
        const typeData = this.typeData();
        if (!typeData) {
            return false;
        }
        return (
            <div className='row search__body__section'>
                <Sidebar bemRoot={"search__body__section__by-types"}
                         list={typeData}/>
            </div>
        );
    }

    render() {
        //spinner setup
        let message = "";
        if (!this.state.tagData && !this.state.typeData){
            message = <div className="w-100 p-5 middle-center">
                Finding the best search terms for you
                <ScaleLoader
                    sizeUnit={"px"}
                    size={75}
                    color={'#7eafbc'}
                    loading={true}/>
            </div>;

        }

        return (
            <div className={'search'} role="main" id="main-content">
                <div className={'search__body container'}>
                    {this.searchTitle_HTML()}
                    <SearchForm history={this.props.history}/>
                    {message}
                    {this.searchByTag_HTML()}
                    {this.searchByType_HTML()}
                </div>
            </div>
        );
    }
}

export default SearchPage;
// export default connect()(SearchPage);
