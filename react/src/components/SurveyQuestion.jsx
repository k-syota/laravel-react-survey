import { PlusIcon } from "@heroicons/react/24/outline";
import React from "react";
import { useEffect, useState } from "react";
import { v4 as uuidv4 } from "uuid";

const [model, setModel] = useState(...survey);

const addQuestion = () => {
  setModel({
    ...model,
    questions: [
      ...model.questions,
      {
        id: uuidv4(),
        type: "text",
        question: "",
        description: "",
        data: {},
      },
    ],
  });
};

const questionChange = (question) => {
  if (!question) {
    return;
  }

  const newQuestions = model.questions.map((q) => {
    if (q.id == question.id) {
      return { ...question };
    }
    return q;
  });
  setModel({
    ...model,
    questions: newQuestions,
  });
};

const deleteQuestion = (question) => {
  const newQuestions = model.questions.filter((q) => q.id !== question.id);
  setModel({
    ...model,
    questions: newQuestions,
  });
};

useEffect(() => {
  onSurveyUpdate(model);
}, [model]);

export default function SurveyQuestion(survey, onSurveyUpdate) {
  return (
    <>
      <div className="flex justify-between">
        <h3 className="text-2xl font-bold">Question</h3>
        <button
          type="button"
          className="flex items-center text-sm py-1 px-4 rounded-sm text-white bg-gray-600 hover:bg-gray-700"
          onClick={addQuestion}
        >
          <PlusIcon className="w-4 mr-2" />
          Add Question
        </button>
      </div>
      {model.questions.length ? (
        model.questions.map((q, ind) => {
          <QuestionEditer
            key={q.id}
            index={ind}
            question={q}
            questionChange={questionChange}
            addQuestion={addQuestion}
            deleteQuestion={deleteQuestion}
          />;
        })
      ) : (
        <div className="text-gray-400 text-center py-4">
          you don't have question created
        </div>
      )}
    </>
  );
}
