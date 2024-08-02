import React from 'react';
import {Query, withApollo} from 'react-apollo';
import Sidebar from "./Sidebar";
import Card from "./Card";
import uuid from "uuid/v4";
import {FaThList, FaTh, FaEdit, FaCopy, FaTrash, FaCode} from 'react-icons/fa';
import {
    q_collectionBySlug,
    q_artifactByKeyword,
    m_updateCollectionTitle,
    m_deleteCollection,
    setCacheState
} from "../graphql/bin/queries";
import {ScaleLoader} from "react-spinners";
import readCache from "../graphql/queries/Cache_ReadState.graphql";

class KeywordPage extends React.Component {
    constructor(props) {
        super(props); // inherit the parent constructor
        this.state = { // default state
            graphQL: false,
            is_collection: this.props.isCollection ? this.props.isCollection : false,
            collection_uri: this.props.isCollection ? this.props.match.params.id : 0,
            collection_id: 0,
            data_ready: false,
            term: this.props.match.params.id ? this.props.match.params.id : false,
            keyword: this.getData(this.props.match.params.id),
            summary_open: true,
            show_cards: false,
            openItems: true,
            is_edit_mode: false,
            doesOwnCollection: false
        };
        // we need to bind the class functions to ensure they have access to the 'this' variable
        this.toggleCurationSummary = this.toggleCurationSummary.bind(this);
        this.formatKeywordData = this.formatKeywordData.bind(this);
        this.formatCollectionData = this.formatCollectionData.bind(this);
        this.onClickOpenAll = this.onClickOpenAll.bind(this);
        this.onClickCloseAll = this.onClickCloseAll.bind(this);
        this.getData = this.getData.bind(this);
        this.artifactCard_HTML = this.artifactCard_HTML.bind(this);
    }

    componentDidMount() {
        window.scrollTo(0, 0);
        console.log(this.state.keyword);
        document.title = "Digital Pedagogy | "+this.props.match.params.id
    }

    onClickOpenAll(e) {
        this.setState((prevState) => {
            return {openItems: true}
        });
    }

    onClickCloseAll(e) {
        this.setState((prevState) => {
            return {openItems: false}
        });
    }

    toggleCurationSummary() {
        this.setState((prevState) => {
            return {summary_open: !prevState.summary_open}
        });
    }

    section(bemRoot, html, sectionName) {
        return (
            <div className={bemRoot + "__section"}>
                {React.isValidElement(html) ?
                    <div className={bemRoot + "__section__" + sectionName}>{html}</div> :
                    <div className={bemRoot + "__section__" + sectionName}
                         dangerouslySetInnerHTML={{__html: html}}/>
                }
            </div>
        );
    }

    /*
        The getData method pulls in the data from the associated queries, formats (where applicable) and writes data to state.
    */
    getData(slug) {
        //will pull from graphql
        if (this.props.isCollection) {
            return this.getData_byCollection(slug);
        } else {
            return this.getData_byKeywords(slug);
        }
    }

    // wrapper for the artifact by keyword query.
    getData_byKeywords(slug) {
        return q_artifactByKeyword(slug.toLowerCase()).then(results => {
            return this.setState({keyword: this.formatKeywordData(results.data.artifacts.edges), data_ready: true});
        });
    }

    // wrapper for the collection query
    getData_byCollection(slug) {
        return q_collectionBySlug(slug.toLowerCase()).then(results => {
            if (!results) {
                this.props.history.push('/404/');
                return false;
            }
            console.log(results.data.collectionBy);
            document.title = "Digital Pedagogy | Collection: "+results.data.collectionBy.title
            return this.setState({
                collection_id: results.data.collectionBy.id,
                keyword: this.formatCollectionData(results.data.collectionBy),
                doesOwnCollection: results.data.collectionBy.author.userId == window.dpVars.userId, // == on purpose
                data_ready: true
            });
        });
    }

    // formats the collection data for use in component
    formatCollectionData = (graphQL) => {
        return ({
            title: graphQL.title,
            artifacts: graphQL.artifacts.edges,
            workCited: this.get_CitationData(graphQL.artifacts.edges)
        })
    };

    // formats the keyword data for the use in component
    formatKeywordData(graphQL) {
        const data = graphQL;
        let title, author, summary, relatedMaterial, workCited, relatedKeyword, artifacts;
        data.forEach((artifact) => {
            let keyword = artifact.node.dp_genres.edges.map((obj) => {
                if (!keyword && obj.node.name.toLowerCase() === 'curation statement') {
                    return true;
                }
            });
            keyword = keyword[0];
            if (keyword) {
                title = this.get_TitleData(artifact);
                author = this.get_AuthorData(artifact);
                summary = this.get_SummaryData(artifact);
            }
        });
        relatedMaterial = this.get_RelatedData(graphQL);
        workCited = this.get_CitationData(graphQL);
        relatedKeyword = this.get_RelatedKeywordData(graphQL);
        artifacts = this.get_ArtifactData(data);
        //console.log(data);
        //
        return {
            title,
            author,
            summary,
            artifacts,
            relatedMaterial,
            relatedKeyword,
            workCited,
        };
    }

    // data formatter
    get_TitleData = (data) => data.node.title;

    // data formatter
    get_AuthorData = (data) => data.node.dp_authors.edges.map((obj, index) => <li
        key={index}><h3
        className={"keyword_body_curator_value"}> {!!obj.node.website? <a href={obj.node.website}>{obj.node.name}</a>: obj.node.name}{obj.node.affiliation && <>, <i>{obj.node.affiliation}</i></>}</h3>
    </li>);

    // data formatter
    get_SummaryData = (data) => data.node.content;

    // data formatter
    get_RelatedData = (data) => {
        let related = [];
        data.forEach((data) => {
            if (data.node.dp_related_works.edges.length > 0) {
                //console.log(data.node.dp_related_works.edges);
                related = [...new Set(related), ...new Set(data.node.dp_related_works.edges.map((obj) => {
                    //console.log(obj.node.description);
                    if (obj.node.description) {
                        return obj.node.description
                    } else if (obj.node.name) {
                        return obj.node.name
                    } else {
                        return [];
                    }

                }))];
            }
        });
        return related;
    };

    // Sort the name of the artifact
    sortObjBySlug(obj) {
        return obj.sort((a, b) => {
            const nameA = a.slug.toUpperCase();
            const nameB = b.slug.toUpperCase();
            let comparison = 0;
            if (nameA > nameB) {
                comparison = 1;
            } else if (nameA < nameB) {
                comparison = -1;
            }
            return comparison;
        })
    }

    // data formatter
    get_CitationData = (data) => {
        let citations = [];
        data.forEach((data) => {
            //const is_keyword_page = !this.props.isCollection && data.node.dp_keywords.edges[0].node.name !== 'curation statement';
            if (!!this.props.isCollection) {
                if (data.node.dp_citations.edges.length > 0 && data.node.dp_genres.edges[0].node.name.toLowerCase() !== 'curation statement') {
                    citations = [...new Set(citations), ...new Set(data.node.dp_citations.edges.map((obj) => {
                        if (obj.node.description) {
                            return {citation:obj.node.description, slug:obj.node.slug}
                        } else if (obj.node.name) {
                            return {citation:obj.node.name, slug:obj.node.slug}
                        } else {
                            return {};
                        }
                    }))];
                }
            } else {
                if (data.node.dp_citations.edges.length > 0 && data.node.dp_genres.edges[0].node.name.toLowerCase() === 'curation statement') {
                    citations = [...new Set(citations), ...new Set(data.node.dp_citations.edges.map((obj) => {
                        if (obj.node.description) {
                            return obj.node.description
                        } else if (obj.node.name) {
                            return obj.node.name
                        } else {
                            return [];
                        }
                    }))];
                }
            }
        });
        if(!!this.props.isCollection) {
            citations = this.sortObjBySlug(citations).map((obj)=> {
                return obj.citation;
            });
        }
        return citations;
    };

    // data formatter
    get_RelatedKeywordData = (data) => {
        let related_keyword = [];
        data.forEach((data) => {
            if (data.node.dp_related_keywords.edges.length > 0) {
                related_keyword = [...new Set(related_keyword), ...new Set(data.node.dp_related_keywords.edges.map((obj) => {
                    if (obj.node.description) {
                        return obj.node.description
                    } else if (obj.node.name) {
                        return obj.node.name
                    } else {
                        return [];
                    }
                }))];
            }
        });
        return related_keyword;
    };

    // data formatter
    get_ArtifactData = (data) => data.filter((dat) => {
        let is_keyword = false;
        dat.node.dp_genres.edges.map((obj) => {
            is_keyword = false;
            if (obj.node.name.toLowerCase() !== 'curation statement') {
                is_keyword = true;
            }
        });
        return is_keyword;
    });

    // data formatter for the side bar component
    sideBarData() {
        const keywordData = this.state.keyword;
        if (keywordData.artifacts.length < 1) {
            return false;
        }
        return {
            title: 'Artifacts',
            items: keywordData.artifacts.map((artifact) => {
                return ({
                    name: artifact.node.title,
                    url: "#" + artifact.node.title.replace(/\s+/g, '-').toLowerCase()
                });
            })
        };
    }

    // this class-like method creates the HTML for the collection title, including the CRUD for in-line editing the collection title
    // Todo: refactor keyWordTitle_HTML to make easier to test. This function should maybe be it's own component.
    keyWordTitle_HTML() {
        const editTitleState = () => {
            if (this.state.is_edit_mode === false) {
                this.setState((prevState) => {
                    return {is_edit_mode: true}
                });
            } else {
                this.setState((prevState) => {
                    return {is_edit_mode: false}
                });
            }
        };

        const updateTitle = (e) => {
            e.preventDefault();
            const data = new FormData(e.target);
            const title = data.get('newTitle');
            document.title = "Digital Pedagogy | Collection: "+title;
            m_updateCollectionTitle(this.state.collection_id, title).then((result) => {
                this.getData(this.props.match.params.id);
                setCacheState({refreshDrawer: "yes"}).then((res) => {
                    this.setState((prevState) => {
                        return {is_edit_mode: false}
                    });
                });
            });
        };

        const deleteThisCollection = (e) => {
            e.preventDefault();
            m_deleteCollection(this.state.collection_id).then((result) => {
                setCacheState({refreshDrawer: "yes"}).then((res) => {
                    this.props.history.push('/');
                });
            });
        };

        const titleForm = (title) => {
            return (
                <>
                    <form onSubmit={updateTitle}>
                        <input name="newTitle" onChange={(e) => {
                            let keyword = this.state.keyword;
                            keyword.title = e.target.value;
                            this.setState((prevState) => {
                                return {keyword: keyword}
                            });
                        }} placeholder={title}/>
                        <button>Save</button>
                    </form>
                </>
            )
        };

        const exportToJson = () => {
            const objectData = this.state.keyword;
            let filename = `${objectData.title}-export.json`;
            let contentType = "application/json;charset=utf-8;";
            if (window.navigator && window.navigator.msSaveOrOpenBlob) {
                var blob = new Blob([decodeURIComponent(encodeURI(JSON.stringify(objectData)))], {type: contentType});
                navigator.msSaveOrOpenBlob(blob, filename);
            } else {
                var a = document.createElement('a');
                a.download = filename;
                a.href = 'data:' + contentType + ',' + encodeURIComponent(JSON.stringify(objectData));
                a.target = '_blank';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        }

        const keyword = this.state.keyword;
        return (
            <>
                <h2 className={"keyword_body_title_label"}>{this.state.is_collection ? `Collection` : 'Keyword'}
                    {!!window.dpVars.userId && this.state.is_collection && (this.state.doesOwnCollection ?
                        <span>
                            <button onClick={editTitleState} alt={"Edit Collection Title"}
                                    aria-label={"Edit Collection Title"}><FaEdit size={14}/></button>
                            <button onClick={deleteThisCollection} alt={"Delete Collection"}
                                    aria-label={"Delete Collection"}><FaTrash
                                size={14}/></button>
                            <button onClick={exportToJson} alt={"Export as JSON"}
                                    aria-label={"Export Collection as JSON"}><FaCode
                                size={14}/></button>
                        </span> :
                        <span>
                        <button onClick={exportToJson} alt={"Export as JSON"}
                                aria-label={"Export Collection as JSON"}><FaCode
                            size={14}/></button>
                        {/*   <button aria-label={"Duplicate Collection"}><FaCopy size={14}/></button> */}
                        </span>)
                    }
                </h2>

                {this.state.is_edit_mode ?
                    titleForm(keyword.title) :
                    <h1 className={"keyword_body_title_value"}>{keyword.title}</h1>
                }
            </>
        );
    }

    // formatted html
    curatorName_HTML() {
        const keyword = this.state.keyword;
        return (
            <div>
                <h2 className={"keyword_body_curator_label"}>Curator</h2>
                <ul>{keyword.author}</ul>
            </div>
        );
    }

    // formatted html
    curatorStatement_HTML() {
        const keyword = this.state.keyword;
        let html = false;
        if (keyword && keyword.summary) {
            html = <div>
                <h2 className={"keyword__body__curator-statement__label"}>Curatorial Statement</h2>
                {
                    this.state.summary_open ?
                        <div
                            dangerouslySetInnerHTML={{__html: keyword.summary.split(" ").splice(0, 50).join(" ") + "... "}}/> :
                        <div dangerouslySetInnerHTML={{__html: keyword.summary}}/>
                }
                <button className={'keyword__body__curator-statement--show-cards'}
                        onClick={() => this.toggleCurationSummary()}>{this.state.summary_open ? 'Read More...' : 'Read Less...'}</button>
            </div>;
        }
        return html;
    }

    // data formatting
    curatorRelatedKeywordData() {
        const keywordData = this.state.keyword;
        return {
            title: 'Related Keywords',
            items: keywordData.relatedKeyword.map((keywords) => ({
                name: keywords,
                url: keywords,
                fullUrl: '/keyword/' + keywords
            }))
        };
    }

    // data formatting
    curatorRelatedMaterialsData() {
        const keywordData = this.state.keyword;
        return {
            title: 'Related Materials',
            items: keywordData.relatedMaterial.map((material) => ({name: material}))
        };
    }

    // data formatting
    curatorWorkCitedData() {
        const keywordData = this.state.keyword;
        return {
            title: keywordData.workCited.length > 1 ? 'Works Cited' : 'Work Cited',
            items: keywordData.workCited.map((work) => ({name: work}))
        };
    }

    // sets up the card classes and loads the card components with the pre-loaded data.
    artifactCard_HTML() {
        const data = this.state.keyword.artifacts;
        //console.log(data);
        let count = 2;
        let previous_category = 'default';
        return data.map((artifact) => {
            let list_class;
            list_class = "";
            if (count === 2) {
                count = count - 1;
                list_class = "first"
            } else if (count === 0) {
                count = 2;
                list_class = "last";
            } else {
                count = count - 1;
            }

            // these props may disappear after we instill a hook/context based state share.
            // currently we have a cache based state system via apollo.

            if (!this.state.is_collection && (artifact.node.categories.edges.length !== 0 && previous_category !== artifact.node.categories.edges[0].node.name)) {
                previous_category = artifact.node.categories.edges[0].node.name;

                return (
                    <> {
                        //giving a key warning
                       }
                        <div className="col-12 artifact__body__wrapper__title">
                            <h3 className="section-header">{artifact.node.categories.edges[0].node.name}</h3>
                        </div>
                        <div className="col-12 artifact__body__wrapper__content description" dangerouslySetInnerHTML={{__html: artifact.node.categories.edges[0].node.description}}/>
                        {console.log(artifact)}
                        <Card key={uuid()}
                              open={this.state.openItems}
                              data={artifact}
                              className={list_class}
                              isCollectionOwner={this.state.doesOwnCollection}
                              collection_id={this.state.collection_id}/>
                    </>
                );
            }

            return (<Card key={uuid()}
                          open={this.state.openItems}
                          data={artifact}
                          className={list_class}
                          isCollectionOwner={this.state.doesOwnCollection}
                          collection_id={this.state.collection_id}/>
            );

        }, this);
    }

    render() {
        if (!this.state.data_ready) {
            //load spinner if the data is not ready yet
            return (<div>
                <div className="w-100 p-5 middle-center">
                    <ScaleLoader
                        sizeUnit={"px"}
                        size={75}
                        color={'#7eafbc'}
                        loading={!this.state.data_ready}/>
                </div>
            </div>);
        }
        if (!this.state.keyword.title) {
            this.props.history.push('/404/' + this.props.match.params.id);
        }
        const buttonOpen = this.state.openItems ? 'open' : 'closed';
        const sideBarData = this.sideBarData();

        if (this.state.refreshData) {
            this.getData(this.props.match.params.id).then(() => {
                this.setState({refreshData: false});
            });
        }
        return (
            <div className='row'>
                <div className='keyword__sidebar col-md-3 col-lg-2' role="aside">
                    {sideBarData &&
                    <nav role="navigation" arialabel="Artifact navigation"
                         className={'d__side__bar navbar sticky-sidebar '}><Sidebar bemRoot={"keyword"}
                                                                                    list={sideBarData}/>
                    </nav>}
                </div>
                <div role="main" id="main-content"
                     className={this.state.is_collection ? `collection-container keyword__body col-sm-12 col-md-9 col-lg-10` : 'keyword-container keyword__body col-sm-12 col-md-9 col-lg-10'}>
                    <div className='keyword__body__container '>
                        <div className='row'>
                            <div className='keyword__body__sections container'>
                                {
                                    this.section("keyword__body", this.keyWordTitle_HTML(), "keyword-title")
                                }

                                {!this.state.is_collection && this.section("keyword__body", this.curatorName_HTML(), "curator-name")}

                                {!this.state.is_collection && this.section("keyword__body", this.curatorStatement_HTML(), "curator-statement")}

                                <div className='keyword__body__section'>
                                    <div className={'row keyword__body__section__controls'}>
                                        <div className={'col-md-3 col-sm-12'}><h2>Artifacts</h2></div>
                                        <div className={'col-md-9 d-sm-block d-none'}>
                                            <div className={'row keyword__body__section_view'}>
                                                <div className="keyword__body__section_view__label">View
                                                </div>
                                                <button onClick={this.onClickOpenAll}
                                                        className={`keyword__body__section_view--open ${buttonOpen}`}
                                                        arialabel="Expand cards">
                                                    <FaThList/>
                                                </button>
                                                <button onClick={this.onClickCloseAll}
                                                        className={` keyword__body__section_view--close ${buttonOpen}`}
                                                        arialabel="Collapse cards">
                                                    <FaTh/>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div key={uuid()} className={`row results-card-container`}>{this.artifactCard_HTML()}</div>
                                </div>
                                {(!this.state.is_collection && this.state.keyword.relatedMaterial.length > 0) &&
                                (<div className='keyword__body__section'>
                                    <Sidebar bemRoot={"keyword__body__section__related-materials"}
                                             list={this.curatorRelatedMaterialsData()}/>
                                </div>)}

                                {
                                    (!!this.state.keyword.workCited.length > 0 &&
                                        <div className='keyword__body__section'>

                                            <Sidebar bemRoot={"keyword__body__section__work-cited"}
                                                     list={this.curatorWorkCitedData()}/>
                                        </div>)}
                                {(!this.state.is_collection && this.state.keyword.relatedKeyword.length > 0) &&
                                (<div className='keyword__body__section'>

                                    <Sidebar bemRoot={"keyword__body__section__related-keywords"}
                                             list={this.curatorRelatedKeywordData()}/>
                                </div>)}

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        )
    }
}

// export default KeywordPage;
export default withApollo(KeywordPage);
