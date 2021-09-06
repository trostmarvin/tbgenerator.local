class App extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            uploadVisible: false,
            selectedCategoryValue: null,
            selectedCategoryError: false,
            selectedContestValue: null,
            selectedContestError: false,
            selectedImage: null,
        };

        this.handleUploadCheckboxChange = this.handleUploadCheckboxChange.bind(this);
        this.handleCategorySelectChange = this.handleCategorySelectChange.bind(this);
        this.handleContestSelectChange = this.handleContestSelectChange.bind(this);
        this.handleUploadImageWidgetChange = this.handleUploadImageWidgetChange.bind(this);
        this.checkFormFieldValues = this.checkFormFieldValues.bind(this);
        this.handleFormSubmit = this.handleFormSubmit.bind(this);
    }

    handleUploadCheckboxChange(e) {
        this.setState({
            uploadVisible: e.target.checked
        });
    }

    handleCategorySelectChange(e) {
        if(e.target.value) {
            this.setState({
                selectedCategoryValue: e.target.value,
                selectedCategoryError: false
            });
        } else {
            this.setState({
                selectedCategoryValue: null,
                selectedCategoryError: true
            });
        }
    }

    handleContestSelectChange(e) {
        if(e.target.value) {
            this.setState({
                selectedContestValue: e.target.value,
                selectedContestError: false
            });
        } else {
            this.setState({
                selectedContestValue: null,
                selectedContestError: true
            });
        }
    }

    handleUploadImageWidgetChange (e) {
        let imagePreviewElement = e.target.parentNode.querySelector('.image-preview');
        imagePreviewElement.setAttribute('src', URL.createObjectURL(e.target.files[0]));
        this.setState({
            selectedImage: e.target.files[0],
        });
    }

    checkFormFieldValues() {
        if(this.state.selectedContestValue === null
            || this.state.selectedCategoryValue === null) {
            return false;
        }

        return true;
    }

    handleFormSubmit(e) {
        e.preventDefault();

        // Check form for errors
        if(!this.checkFormFieldValues()) {
            alert("Please provide all necessary information for the generator to work.");
            return false;
        }

        // Create form data object
        let form = new FormData();

        // Append form data
        form.append("category", this.state.selectedCategoryValue);
        form.append("contest", this.state.selectedContestValue);

        // Add image to requestBody
        if(this.state.uploadVisible === true) {
            console.log(this.state.selectedImage);
            form.append("image", this.state.selectedImage);
        }

        // Send form data with axios
        axios({
            method: 'post',
            url: '/generator',
            data: form,
            headers: {
                'Content-Type': `multipart/form-data;` //  boundary=${form._boundary}
            }
        })
        .then(function (response) {
            console.log(response);
        })
        .catch(function (error) {
            console.log(error);
        });

    }

    render() {
        return (
            <div>
                <form className='mt-3' onSubmit={this.handleFormSubmit}>
                    <h1>Generator Einstellungen</h1>

                    <div className='form-group mb-4'>
                        <select className='form-select mb-1' aria-label='Auswahl der Kategorie'
                            onChange={this.handleCategorySelectChange}>
                            <option defaultValue value=''>Kategorie auswählen</option>
                            <option value='ER'>Elementary Regular Category</option>
                        </select>
                        {   this.state.selectedCategoryError ?
                            <p className='form-error small text-danger'>Bitte wählen Sie eine gültige Kategorie aus</p> :
                            null
                        }
                    </div>

                    <div className='form-group mb-4'>
                        <select className='form-select mb-1' aria-label='Auswahl des Wettbewerbs'
                            onChange={this.handleContestSelectChange}>
                            <option defaultValue value=''>Wettbewerb auswählen</option>
                            <option value='RW'>Regionalwettbewerb</option>
                            <option value='DF'>Deutschlandfinale</option>
                        </select>
                        {   this.state.selectedContestError ?
                            <p className='form-error small text-danger'>Bitte wählen Sie einen gültigen Wettbewerb aus</p> :
                            null
                        }
                    </div>

                    <div className='form-check'>
                        <input className='form-check-input' type='checkbox' id='uploadCheckbox'
                            checked={this.state.uploadVisible}
                            onChange={this.handleUploadCheckboxChange} />
                        <label className='form-check-label' htmlFor='uploadCheckbox'>
                            [Nicht implementiert] Auf vorhandener Karte neue Runde auswürfeln?
                        </label>
                    </div>

                    {this.state.uploadVisible ? <UploadImageWidget handleChangeFunc={this.handleUploadImageWidgetChange} className='mt-1' /> : null}

                    <div className='mt-4'>
                        <input type='submit' value='Generieren' className='btn btn-primary' />
                    </div>

                </form>

                <div>
                    <p>Result:</p>
                    <img />
                </div>

            </div>
        );
    }
}

const UploadImageWidget = (props) => {
    let {handleChangeFunc, ...defaultProps} = props;
    return (
        <div {...defaultProps}>
            <div className="mb-3">
                <label htmlFor="formFile" className="form-label">Bitte vorhandene Karte auswählen (.png/.jpeg)</label>
                <input className="form-control" type="file" accept="image/png, image/jpeg" id="formFile" onChange={props.handleChangeFunc} />
                <div className="p-3">
                    <p className="text-center">Image Preview</p>
                    <img className="image-preview rounded mx-auto d-block img-fluid w-50" />
                </div>
                
            </div>
        </div>
    );
}

ReactDOM.render(< App />, document.querySelector('#app'));