from flask import Flask, request, render_template

app = Flask(__name__)

comments = []

@app.route('/', methods=['GET', 'POST'])
def index():
    message = ""
    if request.method == 'POST':
        comment = request.form.get('comment', '')
        comments.append(comment)
        message = "Your comment has been posted!"
    return render_template('index.html', comments=comments, message=message)

if __name__ == '__main__':
    app.run(debug=True)
