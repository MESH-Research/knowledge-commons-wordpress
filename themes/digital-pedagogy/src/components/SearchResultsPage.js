import React from 'react';
import uuid from "uuid/v4";
import AutosizeInput from 'react-input-autosize';
import {MdKeyboardArrowDown, MdKeyboardArrowRight} from "react-icons/md";
import Card from "./Card"
import {q_pageChildBySearch, q_search, q_searchTax, q_searchByGenre, q_searchByTag} from "../graphql/bin/queries";
import {ScaleLoader} from "react-spinners";
import {Link} from "react-router-dom";

class SearchResultsPage extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            term: this.props.match.params.term.charAt(0).toUpperCase() + props.match.params.term.slice(1).replace(/\+/g, " ").replace(/\=/g, "-"),
            results: {},
            sidebarData: {},
            noResults: true,
            checkedItems: new Map(),
            defaultNumber: 15,
            count_total: 0,
            count_current: 0,
            displayCount: 0,
            recordCount: 0,
            artifactCount: 0,
            ogCount: 0,
            hasNext: false,
            hasPrevious: false,
            cursor: false,
            tag_cursor: false,
            genre_cursor: false,
            search_tax_cursor: false,
            search_page_cursor: false,
            processing: true,
            isFacet: false,
            termTag: false,
            termGenre: false,
            introSearched: false
        };
        this.bemRoot = "search-results";
        this.onCheckboxChange = this.onCheckboxChange.bind(this);
        this.artifactCard_HTML = this.artifactCard_HTML.bind(this);
        this.getArtifactCount = this.getArtifactCount.bind(this);
        this.getData = this.getData.bind(this);
        this.getData_Tags = this.getData_Tags.bind(this);
        this.getData_Genres = this.getData_Genres.bind(this);
        this.getData_Search = this.getData_Search.bind(this);
    }

    componentDidMount() {
        this.getData(this.props.match.params.term.toLowerCase()).then();
        window.scrollTo(0, 0)
        document.title = "Digital Pedagogy | "+this.state.term;
    }

    async dataCallback(results, type) {
        let data = {
            artifacts: {
                pageInfo: {
                    __typename: "WPPageInfo",
                    endCursor: null,
                    hasNextPage: false,
                    hasPreviousPage: false,
                    total: 0,
                    current: 0
                },
                edges: [],
                __typename: "RootQueryToArtifactConnection"
            }
        };

        switch (type) {
            case 'search':
                //console.log(results.data);
                data = await results.data;
                break;
            case 'page':
                //console.log(results);
                let BreakException = {};
                data.artifacts.pageInfo.total = results.data.pages.edges.length;
                await results.data.pages.edges.forEach((v, k, m) => {
                    //Dev Note: forEach does not use return;

                    let obj = {node: v.node};
                    obj['node']['categories'] = {edges: []};
                    obj['node']['dp_keywords'] = {edges: [{node: {name: 'Introduction'}}]};
                    obj['node']['dp_authors'] = {edges: [{node: {name: 'Introduction'}}]};
                    obj['node']['dp_genres'] = {edges: [{node: {name: 'Introduction'}}]};
                    obj['node']['tags'] = {edges: [{node: {name: 'Introduction'}}]};
                    if (v.node.slug !== "introduction") {
                        data.artifacts.edges.push(obj);
                    }
                });
                //console.log(data);
                break;
            case 'tags':
            case 'dp_genres':
            default:
                console.log(results.data[type].edges[0]);
                data = results.data[type].edges[0].node;
                data.artifacts.pageInfo.current = data.artifacts.edges.length;
                break;

        }

        let existing_artifacts = this.state.results.artifacts;
        if (type !== "page" && this.state.cursor && existing_artifacts) {
            //this section is for pagination

            const ids = new Set(existing_artifacts.edges.map(d => d.node.artifactId));
            const newData = data.artifacts.edges.filter(d => !ids.has(d.node.artifactId));
            data.artifacts.edges = [...existing_artifacts.edges, ...newData];

        }
        return data;
    }

    async getData_Tags(term, first = this.state.defaultNumber, isSidebar = false) {
        const data = await q_searchByTag(term, true, first, 0, this.state.tag_cursor).then(async results => {
            return this.dataCallback(results, 'tags');
        });
        this.setState({tag_cursor: data.artifacts.pageInfo.endCursor})
        return data;
    }

    async getData_Genres(term, first = this.state.defaultNumber, isSidebar = false) {
        //console.log('Artifact Type search running...');
        const data = await q_searchByGenre(term, true, first, 0, this.state.genre_cursor).then(async results => {
            return this.dataCallback(results, 'dp_genres');
        });
        this.setState({genre_cursor: data.artifacts.pageInfo.endCursor})
        return data;
    }

    async getData_Search(term, first = this.state.defaultNumber, isSidebar = false) {
        //console.log('Search running...');
        // console.log(this.state.cursor);
        // console.log(first);
        // console.log(isSidebar);
        if (this.state.cursor !== "exit") {
            let cursor = !!isSidebar ? false : this.state.cursor;
            return await q_search(term, first, 0, cursor).then(async results => {
                return this.dataCallback(results, 'search');
            });
        }
        //console.log('exiting main search');
        return false;
    }

    async getData_Pages(term, first = this.state.defaultNumber, isSidebar = false) {
        //console.log('Search Pages running...');
        // console.log(this.state.search_page_cursor);
        // console.log(first);
        // console.log(isSidebar);
        if (this.state.search_page_cursor !== "exit") {
            let cursor = !!isSidebar ? false : this.state.search_page_cursor;
            return await q_pageChildBySearch(term, cursor).then(async results => {
                return this.dataCallback(results, 'page');
            });
        }
        //console.log('exiting page search');
        return false;
    }

    async getData_SearchTax(term, first = this.state.defaultNumber, isSidebar = false) {
        //console.log('Search Tax running...');
        // console.log(this.state.search_tax_cursor);
        // console.log(first);
        // console.log(isSidebar);
        if (this.state.search_tax_cursor !== "exit") {
            let cursor = !!isSidebar ? false : this.state.search_tax_cursor;
            return await q_searchTax(term, first, 0, cursor).then(async results => {
                //console.log(results);
                //this.setState({cursor: results.artifacts.pageInfo.endCursor ? results.artifacts.pageInfo.endCursor : "exit"})
                return this.dataCallback(results, 'search');
            });
        }
        //console.log('exiting tax search');
        return false;
    }

    async getData_MixedSearch(term, first = this.state.defaultNumber, isSidebar = false) {
        // console.log('Search running..');
        let data = Object.entries(this.state.results).length > 0 ? this.state.results : {
            artifacts: {
                pageInfo: {
                    endCursor: null,
                    hasNextPage: false,
                    hasPreviousPage: false,
                    total: 0,
                    current: 0
                },
                edges: [],
            }
        };
        let hasNext, hasPrev, current = 0, totals = 0;
        const search = await this.getData_Search(term, first, isSidebar);
        //console.log(search);
        if (search && search.artifacts.edges.length > 0) {
            // data = search;
            if (search.artifacts.edges.length > 0) {
                data.artifacts.edges = [...data.artifacts.edges, ...search.artifacts.edges];
                this.setState({cursor: search.artifacts.pageInfo.endCursor ? search.artifacts.pageInfo.endCursor : "exit"})
                hasNext = hasNext || search.artifacts.pageInfo.hasNextPage;
                hasPrev = hasPrev || search.artifacts.pageInfo.hasPreviousPage;
                totals = totals + search.artifacts.pageInfo.total;
                current = current + search.artifacts.edges.length;
            }
        }

        const tax = await this.getData_SearchTax(term, first, isSidebar);

        if (tax && tax.artifacts.edges.length > 0) {
            //console.log(tax);
            data.artifacts.edges = [...data.artifacts.edges, ...tax.artifacts.edges];
            this.setState({search_tax_cursor: tax.artifacts.pageInfo.endCursor ? tax.artifacts.pageInfo.endCursor : "exit"})
            hasNext = hasNext || tax.artifacts.pageInfo.hasNextPage;
            hasPrev = hasPrev || tax.artifacts.pageInfo.hasPreviousPage;
            totals = totals + tax.artifacts.pageInfo.total;
            current = current + tax.artifacts.edges.length;
        }

        const page = await this.getData_Pages(term, first, isSidebar);
        //console.log(page)
        if (page && page.artifacts.edges.length > 0) {
            data.artifacts.edges = [...data.artifacts.edges, ...page.artifacts.edges];
            this.setState({search_page_cursor: page.artifacts.pageInfo.endCursor ? page.artifacts.pageInfo.endCursor : "exit"})
            hasNext = hasNext || page.artifacts.pageInfo.hasNextPage;
            hasPrev = hasPrev || page.artifacts.pageInfo.hasPreviousPage;
            totals = totals + page.artifacts.pageInfo.total;
            current = current + page.artifacts.edges.length;
        }

        data.artifacts.pageInfo.hasNextPage = hasNext;
        data.artifacts.pageInfo.hasPreviousPage = hasPrev;
        data.artifacts.pageInfo.total = totals;
        data.artifacts.pageInfo.current = current;

        const unique = [];
        data.artifacts.edges.map(x => unique.filter(a => a.node.title === x.node.title).length > 0 ? null : unique.push(x));
        data.artifacts.edges = unique;
        // console.log(data);
        return data;
    }

// controller for type of search
    async getData() {
        let term = await this.state.term.replace(/\+/g, " ");
        let switcher = await this.props.search_type
        let data;
        switch (switcher) {
            case "tag":
                data = await this.getData_Tags(term);
                if(this.state.results.artifacts) {
                    data.artifacts.edges = [...this.state.results.artifacts.edges, ...data.artifacts.edges]
                }

                break;
            case "type":
                data = await this.getData_Genres(term);
                if(this.state.results.artifacts) {
                    data.artifacts.edges = [...this.state.results.artifacts.edges, ...data.artifacts.edges]
                }
                break;
            case "search":
            default:
                data = await this.getData_MixedSearch(term);
                break;
        }
        this.setState({
            noResults: data.artifacts.pageInfo.total === 0,
            results: data,
            processing: false,
            sidebarData: {...this.state.sidebarData, ...this.formatSidebarData(data)},
            count_total: this.state.count_current === 0 ? data.artifacts.pageInfo.total : 0,
            count_current: !!(data.artifacts.pageInfo.current + this.state.count_current)?data.artifacts.pageInfo.current + this.state.count_current:0,
            hasNext: data.artifacts.pageInfo.hasNextPage,
            hasPrev: data.artifacts.pageInfo.hasPreviousPage,
            termTag: true,
        });

    }

// Accumulates the current value (c) of the array into p and eventually return it.
// Splits an array and counts the number of times a name appears is in the array.
    reducedCounts = (array) => {
        const counts = array.reduce((p, c) => {
            p[c] = p[c] ? p[c] + 1 : 1;
            return p;
        }, {});

        return Object.keys(counts).map((k) => {
            return {
                name: k,
                count: counts[k]
            };
        });
    };

// Sort the name of the artifact
    sortObjByName(obj) {
        return obj.sort((a, b) => {
            const nameA = a.name.toUpperCase();
            const nameB = b.name.toUpperCase();
            let comparison = 0;
            if (nameA > nameB) {
                comparison = 1;
            } else if (nameA < nameB) {
                comparison = -1;
            }
            return comparison;
        })
    }

    formatSidebarData(data) {
        let filterValues = {
            tags: [],
            genres: [],
            keywords: [],
            authors: [],
            curators: []
        };
        data && data.artifacts.edges.map((e) => {
            filterValues.tags = [
                ...filterValues.tags,
                ...(e.node.tags.edges.map((node) => node.node.name))
            ];
            filterValues.keywords = [
                ...filterValues.keywords,
                ...(e.node.dp_keywords.edges.map((node) => node.node.name))
            ];
            filterValues.genres = [
                ...filterValues.genres,
                ...(e.node.dp_genres.edges.map((node) => node.node.name.toLowerCase() === "curation statement" ? "Keyword" : node.node.name))
            ];
            e.node.dp_genres.edges.forEach((f) => {
                if (f.node.name.toLowerCase() === "curation statement") {
                    filterValues.curators = [
                        ...filterValues.curators,
                        ...(e.node.dp_authors.edges.map((node) => node.node.name))
                    ];
                } else {
                    filterValues.authors = [
                        ...filterValues.authors,
                        ...(e.node.dp_authors.edges.map((node) => node.node.name))
                    ];
                }
            });
        });

        return {
            tags: this.sortObjByName(this.reducedCounts(filterValues.tags)),
            keywords: this.sortObjByName(this.reducedCounts(filterValues.keywords)),
            genres: this.sortObjByName(this.reducedCounts(filterValues.genres)),
            authors: this.sortObjByName(this.reducedCounts(filterValues.authors)),
            curators: this.sortObjByName(this.reducedCounts(filterValues.curators))
        };
    }

    onCheckboxChange(e) {
        const item = e.target.name;
        const isChecked = e.target.checked;

        this.setState(prevState => ({
            checkedItems: prevState.checkedItems.set(item, isChecked)
        }), () => {
            const count = this.getArtifactCount(this.state.results.artifacts.edges, this.state.checkedItems);
            this.setState((prevState) => {
                return {
                    artifactCount: count[0], isFacet: isChecked
                }
            });
        });
    }

    getArtifactCount(dataArtifacts, checkedItems) {
        // this does the hiding for the side bar selection.
        let cnt_genres = 0;
        let selected = [];
        checkedItems.forEach((v, k, m) => {
            if (v) {
                dataArtifacts.forEach((dArtifact) => {

                    const g = dArtifact.node.dp_genres.edges.map((obj) => obj.node.name.toLowerCase());
                    const tags = dArtifact.node.tags.edges.map((obj) => obj.node.name.toLowerCase());
                    const authors = dArtifact.node.dp_authors.edges.map((obj) => obj.node.name.toLowerCase());
                    const ch_tags = k.toLowerCase().replace(/tag-|-|=/gi, function (matched) {
                        let mapObj = {
                            "tag-": "",
                            "-": " ",
                            "=": "-"
                        };
                        return mapObj[matched];
                    });
                    const ch_authors = k.toLowerCase().replace(/author-|-|=/gi, function (matched) {
                        let mapObj = {
                            "author-": "",
                            "-": " ",
                            "=": "-"
                        };
                        return mapObj[matched];
                    });
                    const ch_g = k.toLowerCase().replace(/genre-|keyword|-|=/gi, function (matched) {
                        let mapObj = {
                            "genre-": "",
                            "keyword": "curation statement",
                            "-": " ",
                            "=": "-"
                        };
                        return mapObj[matched];
                    });
                    const ch_curator = k.toLowerCase().replace(/curator-|-|=/gi, function (matched) {
                        let mapObj = {
                            "curator-": "",
                            "-": " ",
                            "=": "-"
                        };
                        return mapObj[matched];
                    });
                    let curator = false;
                    if (dArtifact.node.curator) {
                        let curator_ar = dArtifact.node.curator.toLowerCase().split(",").map((item) => {

                            return item.trim()
                        });

                        curator = curator_ar.indexOf(ch_curator) >= 0
                    }

                    if (tags.indexOf(ch_tags) >= 0 || g.indexOf(ch_g) >= 0 || authors.indexOf(ch_authors) >= 0 || curator) {

                        selected = [...new Set(selected), ...new Set([dArtifact])];
                    }
                });
            }
        });

        if (selected.length === 0) {
            //count all articles
            dataArtifacts.forEach((artifact) => {
                cnt_genres = cnt_genres + 1;
                selected = [...new Set(selected), ...new Set([artifact])];
            });
        } else {
            selected.forEach((artifact) => {
                cnt_genres = cnt_genres + 1;
            });
        }

        return [cnt_genres, selected];
    }

    onInputChange(input, event) {
        const newState = {};
        newState[input] = event.target.value;
        this.setState(newState);
    }

//todo: sidebar_HTML should be it's own component
    sideBar_HTML() {
        const data = this.state.sidebarData;
        const bemRoot = this.bemRoot + "__sidebar";
        const tags = data.tags.map((item) => (
            <div key={uuid()} role="aside">
                <input className={"checkbox " + bemRoot + "__tags__checkbox"}
                       type="checkbox"
                       id={'tag-' + item.name.toLowerCase().replace(/\-/g, '=').replace(/ /g, '-')}
                       name={"tag-" + item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-")}
                       checked={this.state.checkedItems.get("tag-" + item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-"))}
                       onChange={this.onCheckboxChange}
                       value={'tag-' + item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-")}/><label
                htmlFor={'tag-' + item.name.toLowerCase().replace(/\-/g, '=').replace(/ /g, '-')}
                className={bemRoot + "__checkbox__text " + bemRoot + "__tags__checkbox__text"}>{item.name}</label>
            </div>)
        );
        const genre = data.genres.map((item) => (
            <div key={uuid()}>
                <input className={"checkbox " + bemRoot + "__genres__checkbox"}
                       type="checkbox"
                       id={'genre-' + item.name.toLowerCase().replace(/\-/g, '=').replace(/ /g, '-')}
                       name={"genre-" + item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-")}
                       checked={this.state.checkedItems.get("genre-" + item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-"))}
                       onChange={this.onCheckboxChange}
                       value={"genre-" + item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-")}/><label
                htmlFor={'genre-' + item.name.toLowerCase().replace(/\-/g, '=').replace(/ /g, '-')}
                className={bemRoot + "__checkbox__text " + bemRoot + "__genres__checkbox__text"}>{item.name}</label>
            </div>)
        );
        console.log(genre.length);
        console.log(genre);

        const author = data.authors.map((item) => (
            <div key={uuid()}>
                <input className={"checkbox " + bemRoot + "__authors__checkbox"}
                       type="checkbox"
                       id={'author-' + item.name.toLowerCase().replace(/\-/g, '=').replace(/ /g, '-')}
                       name={"author-" + item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-")}
                       checked={this.state.checkedItems.get("author-" + item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-"))}
                       onChange={this.onCheckboxChange}
                       value={item.name.toLowerCase().replace(/\-/g, "=").replace(/ /g, "-")}/><label
                htmlFor={'author-' + item.name.toLowerCase().replace(/\-/g, '=').replace(/ /g, '-')}
                className={bemRoot + "__checkbox__text " + bemRoot + "__authors__checkbox__text"}>{item.name}</label>
            </div>)
        );
        return (
            <nav role="navigation" arialabel="Filter options" className={`${bemRoot} navbar sticky-sidebar`}>
                <h3 className={"title " + bemRoot + "__title col-12"}>FILTER TO INCLUDE:</h3>
                <div id="accordion" className={"accordian" + bemRoot + "__filter-accordion col-12"}>
                    {tags.length > 1 &&
                    (<div className={"card " + bemRoot + "__card"}>
                        <div className={"card-header " + bemRoot + "__card-header headingOne"}>
                            <h5 className="mb-0">
                                <button className={"btn btn-link " + bemRoot + "__btn-link"} data-toggle="collapse"
                                        data-target="#tags"
                                        aria-expanded="true" aria-controls="tags">
                                    Tag
                                    <span className={'side-arrow'}><MdKeyboardArrowRight size={20}/></span>
                                    <span className={'down-arrow'}><MdKeyboardArrowDown size={20}/></span>
                                </button>
                            </h5>
                        </div>
                        <div id="tags" className={"collapse show " + bemRoot + "__collapse"} aria-labelledby="tags"
                             data-parent="#accordion">
                            <div className={"card-body " + bemRoot + "__card-body"}>
                                {tags}
                            </div>
                        </div>
                    </div>)}
                    {genre.length > 1 &&
                    (<div className={"card " + bemRoot + "__card"}>
                        <div className={"card-header " + bemRoot + "__card-header headingOne"}>
                            <h5 className="mb-0">
                                <button className={"btn btn-link " + bemRoot + "__btn-link"} data-toggle="collapse"
                                        data-target="#genres"
                                        aria-expanded="false" aria-controls="genres">
                                    Artifact Type
                                    <span className={'side-arrow'}><MdKeyboardArrowRight size={20}/></span>
                                    <span className={'down-arrow'}><MdKeyboardArrowDown size={20}/></span>
                                </button>
                            </h5>
                        </div>
                        <div id="genres" className={"collapse " + bemRoot + "__collapse"} aria-labelledby="tags"
                             data-parent="#accordion">
                            <div className={"card-body " + bemRoot + "__card-body"}>
                                {genre}
                            </div>
                        </div>
                    </div>)}
                    {author.length > 1 &&
                    (<div className={"card " + bemRoot + "__card"}>
                        <div className={"card-header " + bemRoot + "__card-header headingOne"}>
                            <h5 className="mb-0">
                                <button className={"btn btn-link " + bemRoot + "__btn-link"} data-toggle="collapse"
                                        data-target="#authors"
                                        aria-expanded="false" aria-controls="authors">
                                    Authors
                                    <span className={'side-arrow'}><MdKeyboardArrowRight size={20}/></span>
                                    <span className={'down-arrow'}><MdKeyboardArrowDown size={20}/></span>
                                </button>
                            </h5>
                        </div>
                        <div id="authors" className={"collapse  " + bemRoot + "__collapse"} aria-labelledby="tags"
                             data-parent="#accordion">
                            <div className={"card-body " + bemRoot + "__card-body"}>
                                {author}
                            </div>
                        </div>
                    </div>)}
                </div>
            </nav>
        )
    }

    onSubmit(e) {
        e.preventDefault();
        const uri = this.props.search_type ? "/" + this.props.search_type : "/search";
        const data = new FormData(e.target);
        const terms = data.get('search').replace(/ /g, "+");
        //this.props.history.push(uri + "/" + terms);
        //history push is resulting in duplicate records.
        window.location.replace(uri + "/" + terms);
    }

    searchResultsForm_HTML() {
        let comment = "Results for global search of ";
        let form = '';
        let mode = "a";
        switch (this.props.search_type) {
            case "tag":
                comment = "Tag: ";
                break;
            case "type":
                comment = "Artifact type: ";
                break;
            default:
                mode = "b";

                break;
        }

        if (mode === "a") {
            form = <h1>
                <div className={'row'}>
                    <div className="col-md-12 pl-0"><label htmlFor="search-results"><h1>{comment}</h1>
                    </label> "{this.state.term.replace(/[\+]/g, " ")}"
                    </div>
                </div>
            </h1>;
        } else {
            form = <form onSubmit={(e) => this.onSubmit(e)} name="searchForm"
                         className={'row search-results__body__section__form'}>
                <h1>
                    <div className={'row'}>
                        <div className="col-md-12 pl-0"><label htmlFor="search-results"><h1>{comment}</h1>
                        </label><AutosizeInput
                            className={'search-results__body__section__form__text'}
                            name="search"
                            id="search-results"
                            onChange={this.onInputChange.bind(this, 'term')}
                            value={this.state.term.replace(/[\+]/g, " ")}
                            placeholder={this.state.term}/>
                        </div>
                    </div>
                </h1>
            </form>;
        }
        return (
            <div className='row'>
                <div className='search-results__body__section col-md-12 p-0'>
                    {form}
                </div>
            </div>
        );
    }

    searchResultsSummary_HTML() {
        //return this.searchResultsSummary_HTML_1();
    }

    searchResultsSummary_HTML_1() {
        const genres = this.state.artifactCount;
        const maybe_plural_genres = (genres === 1 ? " result " : " results ");
        let aCount = this.state.artifactCount;
        if (typeof this.state.artifactCount === 'object' && this.state.artifactCount !== null
        ) {
            aCount = this.state.artifactCount.total;
        }
        const dCount = this.state.ogCount.total > aCount ? this.state.ogCount.total : aCount;

        let count = this.state.isFacet ? `${aCount} ${maybe_plural_genres}` : `${this.state.displayCount} of ${dCount} ${maybe_plural_genres}`;
        return (
            <div className='row'>
                <div className='search-results__summary col-md-12'>
                    {`Displaying ${count} for your search.`}
                </div>
            </div>
        );
    }

    noResults_HTML() {
        return (
            <div className='no-search-result'>
                <h2 className={''}>No Results Found</h2>
                <div className={'middle-center'}>
    <span>There are no results for {this.state.term}. Try searching again or browse artifacts by <Link
        to="/keyword">keyword</Link>.</span>
                </div>
            </div>
        );
    }

    drag(e, id) {
        e.dataTransfer.setData("text", id);
    }

    artifactCard_HTML() {
        const [, artifacts] = this.getArtifactCount(this.state.results.artifacts.edges, this.state.checkedItems);
        let count = 2;
        return artifacts.map((artifact, index) => {
            let listclass = "";
            if (count === 2) {
                count = count - 1;
                listclass = "first"
            } else if (count === 0) {
                count = 2;
                listclass = "last";
            } else {
                count = count - 1;
            }

            return (<Card key={uuid()} data={artifact} className={listclass}/>);
        });
    }

    render() {
        if (this.state.processing) {

            return (<div>
                <div className="w-100 p-5 middle-center">
                    <ScaleLoader
                        sizeUnit={"px"}
                        size={75}
                        color={'#7eafbc'}
                        loading={true}/>
                </div>

            </div>);
        }

        return (

            <div className='row'>
                <div className='search-results__body col-md-9 col-lg-9' role="main" id="main-content">
                    <div className='search-results__body__container '>
                        {this.searchResultsForm_HTML()}
                        {/*{this.searchResultsSummary_HTML()}*/}
                        <div className={`row results-card-container`}>
                            {!this.state.noResults && this.artifactCard_HTML()}
                            {this.state.noResults && this.noResults_HTML()}
                        </div>
                        {this.state.hasNext && <div onClick={this.getData} className={'row'}>
                            <button className={"col-12 search-results__body__container__loadmore"}>Load more
                                artifacts
                            </button>
                        </div>}
                    </div>
                </div>
                <div className='search-results__sidebar col-md-3 col-lg-3 order-md-first' role="aside">
                    {this.sideBar_HTML()}
                </div>
            </div>

        );
    }
};

export default SearchResultsPage;
