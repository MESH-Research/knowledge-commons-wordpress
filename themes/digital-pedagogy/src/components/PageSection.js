import React from 'react';

class PageSection extends React.Component {
    constructor(props) {
        super(props);
        this.state = {};
        this.props.state && this.props.state();
        this.bemRoot = this.props.bemRoot && this.props.bemRoot + "__";
        this.sectionName = this.props.sectionName ? this.props.sectionName : "default";
        this.html = this.props.html ? this.props.html : "";
    }

    createMarkup() {
        //use if html is JSX
        if (React.isValidElement(this.html)) {
            return (
                <div id={this.sectionName} className={this.bemRoot + 'section__' + this.sectionName}>{this.html}</div>
            );
        }

        //use if is HTML
        return (
            <div id={this.sectionName} className={this.bemRoot + 'section__' + this.sectionName}
                 dangerouslySetInnerHTML={{__html: this.html}}/>
        );
    }

    render() {
        return(<div>
            <div className={this.bemRoot+'section'}>
                {this.createMarkup()}
            </div>
        </div>)
    };
};
export default PageSection;
// export default connect()(PageSection);
